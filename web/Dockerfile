# Stage 1: Build frontend assets
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Production PHP runtime with FrankenPHP
FROM dunglas/frankenphp:1-php8.3-bookworm AS runner

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install essential PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath \
    intl \
    opcache

WORKDIR /app

ENV SERVER_NAME=":8080"
ENV APP_ENV="production"

# Copy project files
COPY . /app

# Copy built Vite assets from Stage 1
COPY --from=build /app/public/build /app/public/build

# Install production PHP dependencies
RUN cp -n .env.example .env || true \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && rm -f .env

# Set required permissions for Laravel storage and cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Setup entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
