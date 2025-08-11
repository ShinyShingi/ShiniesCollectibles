FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create application user
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY --chown=www:www . /var/www/html

# Create storage and bootstrap cache directories with proper permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache && \
    chown -R www:www storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Install PHP dependencies as www user
USER www
RUN composer install --optimize-autoloader

# Expose port 8000
EXPOSE 8000