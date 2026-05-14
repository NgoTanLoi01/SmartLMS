# Sử dụng image PHP 8.4-fpm
FROM php:8.4-fpm

# Cài đặt các công cụ hỗ trợ và thư viện cần thiết
RUN apt-get update && apt-get install -y \
libpng-dev \
libjpeg-dev \
libfreetype6-dev \
libpq-dev \
zip \
libzip-dev \
unzip \
git \
curl

# Cài đặt các phần mở rộng PHP
# Bổ sung pcntl để chạy Reverb (WebSocket)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install pdo_mysql pdo_pgsql pgsql gd zip bcmath pcntl

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Copy mã nguồn
COPY . .

# Phân quyền
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
