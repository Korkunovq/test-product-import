<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Connection;

final class MigrationRunner
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $migrationsPath,
    ) {
    }

    public function migrate(): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT NOW())'
        );

        $applied = $this->connection->fetchFirstColumn('SELECT version FROM schema_migrations');
        $appliedMap = array_flip($applied);

        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files);

        foreach ($files as $file) {
            $version = basename($file);
            if (isset($appliedMap[$version])) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException('Cannot read migration: ' . $file);
            }

            $this->connection->executeStatement($sql);
            $this->connection->insert('schema_migrations', ['version' => $version]);
        }
    }
}
