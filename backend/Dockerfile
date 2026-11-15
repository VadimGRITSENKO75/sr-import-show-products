# ==========================================
# ЭТАП 1: Установка зависимостей через Composer
# ==========================================
FROM composer:2 AS vendor

WORKDIR /app

# Копируем только файлы composer для кэширования слоев
COPY composer.json composer.lock ./

# Устанавливаем production-зависимости (без dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ==========================================
# ЭТАП 2: Финальный образ для запуска
# ==========================================
FROM php:8.3-apache

# Устанавливаем системные зависимости для расширений
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    librabbitmq-dev \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем стандартные расширения PHP
RUN docker-php-ext-install pdo pdo_pgsql zip

# Устанавливаем расширение AMQP (его нет в стандартном наборе, ставим через PECL)
RUN pecl install amqp \
    && docker-php-ext-enable amqp

# Включаем mod_rewrite для Slim
RUN a2enmod rewrite

# Копируем код приложения
COPY . /var/www/html

# Копируем vendor из первого этапа
COPY --from=vendor /app/vendor /var/www/html/vendor

# Настраиваем Apache на папку public (стандарт для Slim)
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Права на запись для папок (кэш, загрузки)
RUN mkdir -p /var/www/html/var/uploads /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads

EXPOSE 80