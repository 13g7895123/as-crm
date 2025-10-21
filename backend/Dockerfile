# ============================================
# Backend Production Dockerfile - Multi-stage Build
# ============================================
# Stage 1: Composer Dependencies
# Stage 2: Production Runtime with PHP-FPM + OPcache

# ============================================
# Stage 1: Composer Dependencies
# ============================================
FROM composer:latest AS composer-stage

WORKDIR /app

# 複製 composer 檔案
COPY composer.json composer.lock* ./

# 安裝生產環境依賴（不含 dev 套件）
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs

# 複製應用程式檔案並產生最佳化 autoload
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# ============================================
# Stage 2: Production Runtime
# ============================================
FROM php:8.1-fpm

LABEL maintainer="CRM RBAC Team"
LABEL description="CRM RBAC Backend Production Environment"

# 安裝系統依賴套件（最小化）
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 安裝 APCu 快取擴充
RUN pecl install apcu && docker-php-ext-enable apcu

# PHP 生產環境配置
RUN { \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/www/html/writable/logs/php-error.log'; \
    echo 'memory_limit = 512M'; \
    echo 'post_max_size = 20M'; \
    echo 'upload_max_filesize = 20M'; \
    echo 'max_execution_time = 30'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/production.ini

# OPcache 最佳化配置（提升效能）
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=0'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# APCu 生產配置
RUN { \
    echo 'apc.enable_cli=0'; \
    echo 'apc.shm_size=128M'; \
    echo 'apc.ttl=7200'; \
    echo 'apc.gc_ttl=3600'; \
} > /usr/local/etc/php/conf.d/apcu.ini

# 設定工作目錄
WORKDIR /var/www/html

# 從 composer stage 複製已安裝的依賴和應用程式
COPY --from=composer-stage /app/vendor ./vendor
COPY --from=composer-stage /app .

# 建立必要目錄
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads public/uploads

# 設定權限（生產環境使用嚴格權限）
RUN chown -R www-data:www-data writable public/uploads \
    && chmod -R 755 writable public/uploads \
    && chmod -R 750 writable/logs

# 切換到非特權使用者
USER www-data

# 開放 9000 port（PHP-FPM）
EXPOSE 9000

# 健康檢查
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -v || exit 1

# 啟動 PHP-FPM
CMD ["php-fpm"]
