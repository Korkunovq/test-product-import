<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\ImportController;
use App\Controller\ProductController;
use App\Database\MigrationRunner;
use App\Fixtures\DataFixtures;
use App\Message\ImportProductsMessage;
use App\MessageHandler\ImportProductsHandler;
use App\Middleware\CorsMiddleware;
use App\Middleware\JwtAuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Repository\ImportJobRepository;
use App\Repository\ProductAttributeRepository;
use App\Repository\ProductImageRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\ImageDownloadService;
use App\Service\ImportUploadService;
use App\Service\ProductImportService;
use App\Service\XlsxParserService;
use DI\Container;
use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;
use Slim\App;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceiver;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpSender;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class AppFactory
{
    public static function buildContainer(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(self::definitions());

        return $builder->build();
    }

    public static function registerMiddleware(App $app): void
    {
        $container = $app->getContainer();
        assert($container instanceof ContainerInterface);

        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        // Outermost last: CORS must run before auth on OPTIONS preflight.
        $app->add($container->get(JwtAuthMiddleware::class));
        $app->add($container->get(RateLimitMiddleware::class));
        $app->add($container->get(CorsMiddleware::class));

        $displayErrors = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $errorMiddleware = $app->addErrorMiddleware($displayErrors, true, true);
        $errorMiddleware->setDefaultErrorHandler(function ($request, $exception) {
            $response = new \Slim\Psr7\Response(500);
            $response->getBody()->write(json_encode(['error' => $exception->getMessage()], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json');
        });
    }

    public static function registerRoutes(App $app): void
    {
        $container = $app->getContainer();
        assert($container instanceof ContainerInterface);

        $resolve = function (string $class, string $method) use ($container): callable {
            return function (...$args) use ($container, $class, $method) {
                $controller = $container->get($class);

                return $controller->$method(...$args);
            };
        };

        $app->get('/api/health', $resolve(HealthController::class, '__invoke'));
        $app->post('/api/auth/login', $resolve(AuthController::class, 'login'));
        $app->get('/api/products', $resolve(ProductController::class, 'list'));
        $app->get('/api/products/{id}', $resolve(ProductController::class, 'show'));
        $app->post('/api/import', $resolve(ImportController::class, 'upload'));
        $app->get('/api/import/{id}', $resolve(ImportController::class, 'status'));

        $app->get('/api/openapi.json', function ($request, $response) {
            $generator = static function (): string {
                ob_start();
                include __DIR__ . '/../../public/openapi.php';

                return (string) ob_get_clean();
            };

            $json = $generator();
            if ($json === '' || !str_starts_with(ltrim($json), '{')) {
                $json = '{}';
            }

            $response->getBody()->write($json);

            return $response->withHeader('Content-Type', 'application/json');
        });

        $app->get('/api/docs', function ($request, $response) {
            $html = file_get_contents(__DIR__ . '/../../public/swagger.html') ?: '';
            $response->getBody()->write($html);

            return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        });

        $storagePath = $_ENV['STORAGE_PATH'] ?? dirname(__DIR__, 2) . '/storage';
        $app->get('/storage/{path:.*}', function ($request, $response, array $args) use ($storagePath) {
            $relative = $args['path'] ?? '';
            $full = realpath($storagePath . '/' . $relative);
            $base = realpath($storagePath);
            if ($full === false || $base === false || !str_starts_with($full, $base) || !is_file($full)) {
                return $response->withStatus(404);
            }

            $mime = mime_content_type($full) ?: 'application/octet-stream';
            $response->getBody()->write((string) file_get_contents($full));

            return $response->withHeader('Content-Type', $mime);
        });
    }

    /** @return array<string, mixed> */
    private static function definitions(): array
    {
        return [
            EntityManagerInterface::class => function (): EntityManagerInterface {
                $paths = [__DIR__ . '/../Entity'];
                $isDev = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
                $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDev);
                if (PHP_VERSION_ID >= 80400) {
                    $config->enableNativeLazyObjects(true);
                }

                $connection = DriverManager::getConnection([
                    'driver' => 'pdo_pgsql',
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'port' => (int) ($_ENV['DB_PORT'] ?? 5432),
                    'dbname' => $_ENV['DB_NAME'] ?? 'products',
                    'user' => $_ENV['DB_USER'] ?? 'app',
                    'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
                ], $config);

                return new EntityManager($connection, $config);
            },

            'messenger.connection' => function (): Connection {
                return Connection::fromDsn($_ENV['MESSENGER_TRANSPORT_DSN'] ?? 'amqp://guest:guest@localhost:5672/%2f/messages');
            },

            'messenger.transport' => function (ContainerInterface $c): AmqpTransport {
                $connection = $c->get('messenger.connection');

                return new AmqpTransport($connection, new PhpSerializer());
            },

            'messenger.receiver' => fn (ContainerInterface $c) => new AmqpReceiver($c->get('messenger.connection'), new PhpSerializer()),

            'messenger.handlers' => fn (ContainerInterface $c) => new HandlersLocator([
                ImportProductsMessage::class => [$c->get(ImportProductsHandler::class)],
            ]),

            'messenger.bus.worker' => fn (ContainerInterface $c) => new MessageBus([
                new HandleMessageMiddleware($c->get('messenger.handlers')),
            ]),

            MessageBusInterface::class => function (ContainerInterface $c): MessageBusInterface {
                $sendersMap = [
                    ImportProductsMessage::class => ['async'],
                ];
                $sendersLocator = new SendersLocator($sendersMap, new class($c) implements \Psr\Container\ContainerInterface {
                    public function __construct(private ContainerInterface $c)
                    {
                    }

                    public function get(string $id): mixed
                    {
                        return new AmqpSender($this->c->get('messenger.connection'), new PhpSerializer());
                    }

                    public function has(string $id): bool
                    {
                        return $id === 'async';
                    }
                });

                return new MessageBus([
                    new SendMessageMiddleware($sendersLocator),
                    new HandleMessageMiddleware($c->get('messenger.handlers')),
                ]);
            },

            MigrationRunner::class => fn (ContainerInterface $c) => new MigrationRunner(
                $c->get(EntityManagerInterface::class)->getConnection(),
                dirname(__DIR__, 2) . '/migrations'
            ),

            'migrations.runner' => fn (ContainerInterface $c) => $c->get(MigrationRunner::class),

            XlsxParserService::class => fn () => new XlsxParserService(),

            ImageDownloadService::class => fn () => new ImageDownloadService(
                $_ENV['STORAGE_PATH'] ?? dirname(__DIR__, 2) . '/storage',
                $_ENV['PUBLIC_STORAGE_URL'] ?? 'http://localhost/storage'
            ),

            AuthService::class => fn (ContainerInterface $c) => new AuthService(
                $c->get(UserRepository::class),
                $_ENV['JWT_SECRET'] ?? 'dev-secret-change-me-32chars-min',
                (int) ($_ENV['JWT_TTL'] ?? 86400)
            ),

            ImportUploadService::class => fn (ContainerInterface $c) => new ImportUploadService(
                $c->get(EntityManagerInterface::class),
                $c->get(ImportJobRepository::class),
                $c->get(MessageBusInterface::class),
                $_ENV['STORAGE_PATH'] ?? dirname(__DIR__, 2) . '/storage',
                (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760)
            ),

            ProductImportService::class => fn (ContainerInterface $c) => new ProductImportService(
                $c->get(EntityManagerInterface::class),
                $c->get(ProductRepository::class),
                $c->get(ImportJobRepository::class),
                $c->get(XlsxParserService::class),
                $c->get(ImageDownloadService::class)
            ),

            ProductRepository::class => fn (ContainerInterface $c) => new ProductRepository($c->get(EntityManagerInterface::class)),
            ProductAttributeRepository::class => fn (ContainerInterface $c) => new ProductAttributeRepository($c->get(EntityManagerInterface::class)),
            ProductImageRepository::class => fn (ContainerInterface $c) => new ProductImageRepository($c->get(EntityManagerInterface::class)),
            ImportJobRepository::class => fn (ContainerInterface $c) => new ImportJobRepository($c->get(EntityManagerInterface::class)),
            UserRepository::class => fn (ContainerInterface $c) => new UserRepository($c->get(EntityManagerInterface::class)),

            ImportProductsHandler::class => fn (ContainerInterface $c) => new ImportProductsHandler($c->get(ProductImportService::class)),
            DataFixtures::class => fn (ContainerInterface $c) => new DataFixtures(
                $c->get(EntityManagerInterface::class),
                $c->get(ProductRepository::class),
                $c->get(UserRepository::class)
            ),

            CorsMiddleware::class => fn () => new CorsMiddleware($_ENV['CORS_ORIGIN'] ?? 'http://localhost:4200'),
            JwtAuthMiddleware::class => fn (ContainerInterface $c) => new JwtAuthMiddleware($c->get(AuthService::class)),
            RateLimitMiddleware::class => fn () => new RateLimitMiddleware(
                (int) ($_ENV['IMPORT_RATE_LIMIT'] ?? 5),
                (int) ($_ENV['IMPORT_RATE_WINDOW'] ?? 60)
            ),

            HealthController::class => fn (ContainerInterface $c) => new HealthController($c->get(EntityManagerInterface::class)),
            AuthController::class => fn (ContainerInterface $c) => new AuthController($c->get(AuthService::class)),
            ProductController::class => fn (ContainerInterface $c) => new ProductController($c->get(ProductRepository::class)),
            ImportController::class => fn (ContainerInterface $c) => new ImportController(
                $c->get(ImportUploadService::class),
                $c->get(ImportJobRepository::class)
            ),
        ];
    }
}
