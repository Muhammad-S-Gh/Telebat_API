# Use official PHP image
FROM php:8.3-fpm


# Prevent interactive prompts
ENV DEBIAN_FRONTEND=noninteractive


# Install required system packages
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    wget \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    build-essential \
    autoconf \
    pkg-config \
    libavcodec-extra \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    bcmath

# Install and Enable mb_string
RUN docker-php-ext-install mbstring \
    && docker-php-ext-enable mbstring

# Install Redis extension
RUN pecl channel-update pecl.php.net \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install supercronic (a single‑binary cron daemon)
RUN wget -qO /usr/local/bin/supercronic \
    "https://github.com/aptible/supercronic/releases/download/v0.2.33/supercronic-linux-amd64" \
    && chmod +x /usr/local/bin/supercronic

# Copy the crontab file into the image
RUN mkdir -p /etc/supercronic
COPY crontab.ini /etc/supercronic/crontab

# Copy composer from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy uploads settings
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Copy Supervisor config for queue workers
COPY queue-worker.conf /etc/supervisor/conf.d/queue-worker.conf

# Set working directory inside container
WORKDIR /var/www
