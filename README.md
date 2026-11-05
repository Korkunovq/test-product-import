# Product Import

Веб-приложение для импорта товаров из Excel (`.xlsx`): асинхронная обработка через очередь, сохранение в PostgreSQL, просмотр списка и карточки товара.

## Стек

| Слой | Технологии |
|------|------------|
| Backend | PHP 8.4, Slim 4, Doctrine ORM, Symfony Messenger |
| Frontend | Angular 19, NgRx, Angular Material |
| БД / очередь | PostgreSQL, RabbitMQ |
| Infra | Docker Compose (app, worker, db, rabbitmq, nginx, frontend) |

## Быстрый запуск

Нужны **Docker Desktop** и файл окружения:

```powershell
Copy-Item backend\.env.example backend\.env
```

### Windows (рекомендуется)

Скрипт поднимает контейнеры, применяет миграции и загружает фикстуры:

```powershell
.\scripts\start.ps1
```

### Makefile / Linux / macOS

```bash
cp backend/.env.example backend/.env
make up
```

### Что делает быстрый старт

1. `docker compose up -d --build` — сборка и запуск сервисов
2. миграции БД
3. фикстуры (пользователь + демо-товар)

## После запуска

| Сервис | URL |
|--------|-----|
| UI | http://localhost:4200 |
| API | http://localhost:8080/api |
| Swagger UI | http://localhost:8080/api/docs |
| OpenAPI JSON | http://localhost:8080/api/openapi.json |
| RabbitMQ | http://localhost:15672 (`guest` / `guest`) |

**Логин:** `admin@example.com` / `admin123`

## Тесты

### Backend (PHPUnit)

CRUD репозитория, парсер xlsx, расчёт скидки:

```bash
cd backend
composer test
```

Статический анализ:

```bash
cd backend
composer phpstan
```

### Frontend unit

```bash
cd frontend
npm run test -- --watch=false
```

### E2E (Playwright)

Сначала поднимите Docker-стек, затем:

```bash
cd frontend
npx playwright install chromium
npm run e2e
```

Покрывает: редирект на логин, вход, список товаров, импорт, карточку, выход.
