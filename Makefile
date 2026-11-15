.PHONY: up down migrate test worker logs

# Запуск всех контейнеров в фоне с пересборкой
up:
	docker compose up -d --build

# Остановка контейнеров
down:
	docker compose down

# Применение миграций базы данных
migrate:
	docker compose exec app ./vendor/bin/doctrine-migrations migrate --no-interaction

# Запуск тестов PHPUnit
test:
	docker compose exec app vendor/bin/phpunit

# Запуск воркера RabbitMQ (в отдельном терминале)
worker:
	docker compose exec app php bin/worker.php

# Просмотр логов приложения
logs:
	docker compose logs -f app