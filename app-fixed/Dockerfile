FROM php:8.2-apache

# 安裝 PHP 必要的擴充套件
RUN docker-php-ext-install pdo pdo_mysql mysqli exif

# 將 Apache 預設的網頁根目錄修改為 /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 啟用 Apache 的 rewrite 模組
RUN a2enmod rewrite

# 設定工作目錄
WORKDIR /var/www/html

# 安裝 gcc 用於編譯 C 語言漏洞演示程式
RUN apt-get update && apt-get install -y gcc && rm -rf /var/lib/apt/lists/*

# 複製並編譯 C 程式
COPY vuln_process.c /usr/src/vuln_process.c
RUN gcc -o /usr/local/bin/vuln-process /usr/src/vuln_process.c
