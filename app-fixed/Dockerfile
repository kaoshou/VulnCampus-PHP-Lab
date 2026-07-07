FROM php:8.2-apache

# 安裝 PHP 必要的擴充套件
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 將 Apache 預設的網頁根目錄修改為 /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 啟用 Apache 的 rewrite 模組
RUN a2enmod rewrite

# 設定工作目錄
WORKDIR /var/www/html
