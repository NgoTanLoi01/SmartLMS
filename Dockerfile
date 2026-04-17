# Sử dụng phiên bản PHP 8.3 ổn định nhất cho Docker hiện tại
FROM php:8.4-fpm

# Cài đặt các công cụ hỗ trợ và thư viện cần thiết cho PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    curl

# Cài đặt các phần mở rộng PHP (PDO MySQL để kết nối DB, GD để xử lý ảnh)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath

# Copy công cụ Composer từ image chính thức vào container này
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc bên trong container
WORKDIR /var/www/html

# Copy toàn bộ mã nguồn dự án vào trong container
COPY . .

# Phân quyền cho thư mục storage và cache để Laravel có thể ghi file
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Mở cổng 9000 (cổng mặc định của PHP-FPM)
EXPOSE 9000

CMD ["php-fpm"]