# SR Import Show Products

## 🐘 Backend (PHP + Slim + Doctrine)
Бэкенд представляет собой REST API на базе Slim 4, который обрабатывает импорт товаров из .xlsx, управляет списком товаров и отдаёт данные для фронтенда.

### ⚙️ Стек и зависимости

*   **PHP 8.3** (расширения: `pdo_pgsql`, `zip`, `amqp`)
*   **Slim 4** — микрофреймворк для API
*   **Doctrine ORM 3** — работа с базой данных (PostgreSQL)
*   **Symfony Messenger + RabbitMQ** — асинхронная обработка импорта
*   **PhpSpreadsheet** — парсинг Excel-файлов
*   **Guzzle** — скачивание изображений по URL
*   **Ramsey/UUID** — генерация уникальных ID для задач импорта
*   **PHPUnit** — тестирование
*   **PHPStan** (level 5) и **PHP CS Fixer** — статический анализ и стиль кода

### 🚀 Запуск (локально, без Docker)
Перейдите в папку backend:

```bash
cd backend
```
Установите зависимости:

```bash
composer install
```
Настройте окружение. Скопируйте .env.example в .env и укажите параметры подключения к PostgreSQL и RabbitMQ:

```bash
cp .env.example .env
```
Пример содержимого .env:

```ini
APP_ENV=development

DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=app_db
DB_USER=postgres
DB_PASSWORD=secret

RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
```
Примените миграции:

```bash
vendor/bin/doctrine-migrations migrate --no-interaction
```
Запустите встроенный сервер PHP:

```bash
php -S localhost:8080 -t public
```
API будет доступно по адресу http://localhost:8080.

В отдельном терминале запустите воркер для обработки очереди RabbitMQ:

```bash
php bin/worker.php
```
Воркер должен «зависнуть» — это нормально, он слушает очередь.

### 🧪 Тестирование
Для запуска интеграционных тестов (используют отдельную базу app_db_test):

```bash
vendor/bin/phpunit
```
Для проверки статического анализа и стиля кода:

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```
### 📂 Структура бэкенда
*   `src/Domain/` — сущности Doctrine (`Product`, `ProductAttribute`, `ProductImage`).
*   `src/Controller/` — контроллеры API (`ProductController`, `ImportController`).
*   `src/Service/ImportService.php` — логика парсинга `.xlsx`, скачивания картинок и сохранения в БД.
*   `src/Message/` и `src/MessageHandler/` — сообщение и обработчик для Symfony Messenger.
*   `src/Middleware/RateLimitMiddleware.php` — ограничение частоты запросов на импорт.
*   `config/routes.php` — все маршруты API.
*   `config/container.php` и `config/dependencies.php` — настройка DI-контейнера (PHP-DI).
*   `bin/worker.php` — скрипт для запуска воркера RabbitMQ.
*   `tests/` — PHPUnit тесты (интеграционные).
*   `public/docs/` — Swagger UI (интерактивная документация).

### 📝 Основные эндпоинты API
*   `POST /api/import` — загрузка `.xlsx` файла (возвращает `taskId`). Защищён Rate Limit (5 запросов/мин).
*   `GET /api/import/status/{taskId}` — получение статуса и отчёта об импорте.
*   `GET /api/import/list` — история импортов.
*   `GET /api/products?page=1&limit=20&name=...` — список товаров с пагинацией и поиском.
*   `GET /api/products/{id}` — карточка товара с атрибутами и изображениями.
*   `GET /api/health` — проверка здоровья приложения и БД.
*   `GET /docs` — Swagger UI (интерактивная документация).

### ⚠️ Особенности реализации
*   **Асинхронность**: Импорт запускается через очередь RabbitMQ. Фронтенд получает `taskId` и опрашивает статус, пока воркер не закончит обработку.
*   **Upsert**: Повторный импорт того же файла не создаёт дубликатов — товары обновляются по `external_code`.
*   **Транзакции**: Каждая строка файла обрабатывается в отдельной транзакции. Если строка невалидна, она пропускается, а ошибка записывается в отчёт.
*   **Скачивание изображений**: При импорте ссылки на фото (колонка AM) скачиваются в `public/uploads` и привязываются к товару.
*   **PostgreSQL**: Используется драйвер `pdo_pgsql`. Для поиска без учёта регистра применяется `LOWER()`.
*   **Именование колонок**: `UnderscoreNamingStrategy` автоматически преобразует `camelCase` в `snake_case` (например, `externalCode` → `external_code`).

## 🅰️ Frontend (Angular + NgRx)
Фронтенд — это одностраничное приложение (SPA) на Angular 21, которое взаимодействует с REST API бэкенда. Интерфейс построен с использованием UI-кита Angular Material.

### ⚙️ Стек и зависимости

*   **Angular 21** (standalone-компоненты)
*   **NgRx (Store, Effects)** — управление состоянием списка товаров
*   **Angular Material** — UI-компоненты (таблицы, карточки, формы, пагинация)
*   **RxJS** — асинхронные запросы и опрос статуса импорта
*   **Cypress** — E2E-тестирование
*   **TypeScript** (strict: true)

### 🚀 Запуск (локально)
Перейдите в папку frontend:

```bash
cd frontend
```
Установите зависимости:

```bash
npm install
```
Запустите сервер разработки:

```bash
npm start
```
Приложение будет доступно по адресу http://localhost:4200.

Важно: Для работы фронтенда бэкенд должен быть запущен на http://localhost:8080. Если вы запускаете всё через Docker, фронтенд всё равно запускается локально (или в отдельном контейнере, если настроено).

### 🧪 Тестирование (E2E)
Убедитесь, что фронтенд и бэкенд запущены. Затем выполните:

```bash
npx cypress run --e2e
```
Тесты проверяют навигацию по списку товаров и переход в карточку товара.

### 📂 Структура фронтенда
*   `src/app/models/` — TypeScript-интерфейсы (`Product`, `ProductAttribute`, `ProductImage`, `ImportReport`).
*   `src/app/services/` — сервисы для работы с API (`ProductService`, `ImportService`).
*   `src/app/store/products/` — NgRx Store (Actions, Reducer, Effects) для списка товаров.
*   `src/app/components/` — компоненты страниц:
    *   `product-list/` — список товаров с пагинацией и поиском.
    *   `product-card/` — карточка товара с атрибутами и изображениями.
    *   `import/` — страница загрузки `.xlsx` файла.
    *   `import-list/` — история импортов.
    *   `import-report/` — детальный отчёт об импорте.
*   `src/app/app.routes.ts` — маршрутизация (lazy loading для всех компонентов).
*   `cypress/e2e/` — E2E-тесты.

### 📝 Основные страницы
*   **Список товаров** (`/products`) — таблица с пагинацией, поиском по названию и фильтрацией (данные запрашиваются с сервера). Для управления состоянием используется NgRx.
*   **Карточка товара** (`/products/:id`) — полная информация о товаре, включая дополнительные атрибуты и изображения.
*   **Импорт товаров** (`/import`) — загрузка `.xlsx` файла, отображение прогресса и переход на страницу отчёта после завершения.
*   **История импортов** (`/imports`) — таблица со всеми выполненными импортами и ссылками на детальные отчёты.
*   **Отчёт об импорте** (`/import/report/:taskId`) — детальная информация о результатах импорта (сколько товаров импортировано, список ошибок, если есть).

### ⚠️ Особенности реализации
*   **NgRx**: Список товаров загружается через `ProductsActions.loadProducts`, обрабатывается в `ProductsEffects` и сохраняется в Store. Компонент использует `async pipe` и селекторы (`selectAllProducts`, `selectProductsLoading`, `selectTotal`).
*   **Опрос статуса импорта**: После загрузки файла фронтенд получает `taskId` и опрашивает эндпоинт `/api/import/status/{taskId}` каждые 2 секунды. Когда статус становится `done` или `done_with_errors`, опрос прекращается, и пользователь перенаправляется на страницу отчёта.
*   **Lazy loading**: Все компоненты загружаются лениво через `loadComponent` в `app.routes.ts`, что ускоряет первоначальную загрузку приложения.
*   **Angular Material**: Для таблиц используется `MatTable`, для карточек — `MatCard`, для пагинации — `MatPaginator`, для полей ввода — `MatFormField` и `MatInput`.
*   **Zoneless**: Приложение работает без Zone.js (по умолчанию в Angular 21), поэтому для обновления UI после асинхронных операций используется `ChangeDetectorRef` (в компонентах импорта).

## 🐳 Docker (Инфраструктура)
Проект полностью контейнеризирован. В корне лежит docker-compose.yml, который поднимает три сервиса: PHP-приложение (Apache), PostgreSQL и RabbitMQ.

### 🚀 Запуск через Docker
Убедитесь, что Docker Desktop запущен.

Выполните в корне проекта:

```bash
docker compose up -d --build
```
Примените миграции внутри контейнера:

```bash
docker compose exec app ./vendor/bin/doctrine-migrations migrate --no-interaction
```
Запустите воркер RabbitMQ (в отдельном терминале):

```bash
docker compose exec app php bin/worker.php
```
Приложение доступно: http://localhost:8080

RabbitMQ Management: http://localhost:15672 (guest/guest)

## 🛑 Остановка
```bash
docker compose down
```
(Данные в PostgreSQL сохраняются в томе postgres_data, поэтому не теряются при перезапуске).

### ⚙️ Особенности Docker-сборки
Multi-stage сборка: Dockerfile состоит из двух этапов. Первый этап (composer:2) устанавливает зависимости. Второй этап (php:8.3-apache) — чистый образ, в который копируется только готовый код и vendor. Это уменьшает размер финального образа и убирает инструменты сборки.

Расширения PHP: В образ устанавливаются pdo_pgsql (для PostgreSQL) и amqp (для RabbitMQ) через docker-php-ext-install и pecl.

Права доступа: Папки var/uploads и public/uploads монтируются как тома, чтобы скачанные картинки не пропадали при перезапуске контейнера.

## 🔄 CI/CD (GitHub Actions)
Для автоматической проверки качества кода настроен workflow .github/workflows/ci.yml. Он запускается при каждом push и pull_request в ветки main и master.

### 📋 Что делает CI:
Устанавливает PHP 8.3 с расширениями pdo_pgsql, zip, amqp.

Устанавливает зависимости через Composer.

Запускает PHPStan (level 5) — статический анализ.

Запускает PHP CS Fixer (в режиме dry-run) — проверка стиля кода.

Запускает PHPUnit — интеграционные тесты (используется сервис PostgreSQL в контейнере).

## 📂 Файл конфигурации:
```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  backend:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: ./backend
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo, pdo_pgsql, zip, amqp
          tools: composer:v2
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-interaction
      - name: PHPStan
        run: vendor/bin/phpstan analyse --no-progress
      - name: PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no
      - name: PHPUnit
        run: vendor/bin/phpunit
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: app_db_test
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
        ports: ['5432:5432']
        options: --health-cmd pg_isready --health-interval 10s --health-timeout 5s --health-retries 5
```
## 📄 Swagger / OpenAPI (Документация API)
API документировано с помощью OpenAPI (Swagger). Аннотации добавлены в контроллеры (ProductController, ImportController) с использованием библиотеки zircote/swagger-php.

### 📍 Где посмотреть:
После запуска бэкенда откройте в браузере:
http://localhost:8080/docs

Вы увидите интерактивную страницу Swagger UI, где можно посмотреть все эндпоинты, их параметры и попробовать отправить запросы прямо из браузера.

### 🛠️ Как обновить спецификацию:
Если вы изменили аннотации в коде, перегенерируйте JSON-файл:

```bash
./vendor/bin/openapi src -o public/docs/openapi.json
```
Swagger UI автоматически подхватит обновлённый JSON.

### ⚙️ Переменные окружения (.env)
Все настройки подключения к базе данных и RabbitMQ вынесены в файл backend/.env. Для удобства в репозитории лежит backend/.env.example — копируйте его и заполните своими значениями.

Пример .env.example:
```ini
# Окружение (development / production)
APP_ENV=development

# PostgreSQL
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=app_db
DB_USER=postgres
DB_PASSWORD=secret

# RabbitMQ
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
```
⚠️ Важно для Docker:
При запуске через docker compose переменные окружения для сервиса app переопределяются в docker-compose.yml (хост db вместо 127.0.0.1). Это сделано для того, чтобы контейнеры могли обращаться друг к другу по именам сервисов.

## 📂 Полная структура проекта

```text
sr-import-show-products/
├── .github/
│   └── workflows/
│       └── ci.yml                 # GitHub Actions (CI)
├── backend/                       # PHP-приложение (Slim + Doctrine)
│   ├── bin/
│   │   ├── worker.php             # Воркер RabbitMQ
│   │   └── seed.php               # Сидеры (fixtures)
│   ├── config/
│   │   ├── routes.php             # Маршруты API
│   │   ├── dependencies.php       # DI-контейнер
│   │   └── settings.php           # Настройки приложения
│   ├── public/
│   │   ├── index.php              # Точка входа
│   │   ├── uploads/               # Скачанные изображения
│   │   └── docs/                  # Swagger UI
│   ├── src/
│   │   ├── Domain/                # Сущности Doctrine
│   │   ├── Controller/            # API-контроллеры
│   │   ├── Service/               # Бизнес-логика
│   │   ├── Message/               # Сообщения Messenger
│   │   ├── MessageHandler/        # Обработчики сообщений
│   │   └── Middleware/            # Rate Limiting
│   ├── tests/                     # PHPUnit тесты
│   ├── var/
│   │   ├── uploads/               # Загруженные xlsx и отчёты
│   │   └── rate_limit/            # Файлы счётчиков Rate Limit
│   ├── .env.example
│   ├── Dockerfile                 # Multi-stage сборка
│   ├── phpstan.neon
│   └── .php-cs-fixer.dist.php
├── frontend/                      # Angular-приложение
│   ├── src/
│   │   ├── app/
│   │   │   ├── components/        # Компоненты страниц
│   │   │   ├── models/            # TypeScript-интерфейсы
│   │   │   ├── services/          # Сервисы для API
│   │   │   ├── store/             # NgRx Store
│   │   │   └── app.routes.ts      # Маршрутизация
│   ├── cypress/                   # E2E-тесты
│   └── package.json
├── docker-compose.yml             # Инфраструктура (App, DB, RabbitMQ)
├── Makefile                       # Команды для управления
└── README.md
```