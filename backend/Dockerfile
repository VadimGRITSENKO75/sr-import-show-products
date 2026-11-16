# ==========================================
# ЭТАП 1: Установка зависимостей через Composer
# ==========================================
FROM composer:2.7 AS vendor

WORKDIR /app

# Копируем только файлы composer для кэширования слоев
COPY composer.json composer.lock ./

# Устанавливаем production-зависимости (без dev)
# --ignore-platform-req=ext-gd нужен, потому что в образе composer нет GD,
# но он появится в финальном образе
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-req=ext-gd

# ==========================================
# ЭТАП 2: Финальный образ для запуска
# ==========================================
FROM php:8.3-apache

COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer

# Устанавливаем системные зависимости для расширений (включая библиотеки для GD)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    librabbitmq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем стандартные расширения PHP, включая GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd

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

RUN echo "DirectorySlash Off" >> /etc/apache2/apache2.conf

EXPOSE 80