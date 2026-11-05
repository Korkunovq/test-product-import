.PHONY: up down build migrate fixtures seed test backend-test frontend-test

up:
	docker compose up -d --build
	docker compose exec app php bin/console migrations:migrate
	docker compose exec app php bin/console fixtures:load

down:
	docker compose down

build:
	docker compose build

migrate:
	docker compose exec app php bin/console migrations:migrate

fixtures:
	docker compose exec app php bin/console fixtures:load

backend-test:
	cd backend && composer test

frontend-test:
	cd frontend && npm run test -- --watch=false

test: backend-test frontend-test
