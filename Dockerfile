# ---- Stage 1: build frontend assets (Tailwind via Vite) ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 2: PHP-FPM + Nginx runtime (single container for Render) ----
FROM php:8.3-fpm-alpine

# Install system dependencies + nginx + supervisor (Render runs one process
# per service, so php-fpm and nginx are supervised together in this image)
RUN apk add --no-cache \
    git \
    curl \
    curl-dev \
    zip \
    unzip \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    oniguruma-dev \
    nginx \
    supervisor

# Install PHP extensions needed by LavaLust
# NOTE: extensions are installed one-at-a-time (no -j$(nproc), no multi-extension
# batching) because docker-php-ext-install's build/cleanup steps race on shared
# modules/.libs directories when multiple extensions are compiled together,
# causing intermittent "cp: can't stat 'modules/*'" failures.
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install gd
RUN docker-php-ext-install curl
# NOTE: "json" is intentionally NOT installed here - it has been a built-in,
# statically-compiled core extension since PHP 8.0 and is already enabled.
# Running docker-php-ext-install json produces no modules/ output and fails
# with "cp: can't stat 'modules/*'".
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install bcmath

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Bring in the frontend build output produced in the assets stage
COPY --from=assets /app/public/build ./public/build

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy nginx + supervisor config
# NOTE: Alpine's nginx package (unlike Debian/Ubuntu's) includes
# /etc/nginx/conf.d/*.conf at the ROOT context (outside http {}) and
# /etc/nginx/http.d/*.conf INSIDE http {} - server blocks must go in
# http.d here, or nginx fails with "server directive is not allowed here".
COPY docker/nginx/nginx.render.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Install PHP dependencies (if composer.json is present)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Create necessary directories
RUN mkdir -p /var/www/html/runtime/sessions

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# NOTE: stays root here (not "USER www-data") because supervisord needs to
# start both nginx and php-fpm; php-fpm's pool config already runs workers
# as www-data.

# Render injects PORT at runtime; start.sh writes it into nginx.conf before
# launching supervisord.
ENV PORT=10000
EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
