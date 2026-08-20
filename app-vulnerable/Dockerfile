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

# 安裝 gcc 用於編譯 C 語言漏洞演示程式，以及 iputils-ping 用於命令注入演示
RUN apt-get update && apt-get install -y gcc iputils-ping && rm -rf /var/lib/apt/lists/*

# 複製並編譯 C 程式 (弱點版關閉 stack protector 以便完美重現 Segmentation Fault 退出碼)
COPY vuln_process.c /usr/src/vuln_process.c
RUN gcc -fno-stack-protector -o /usr/local/bin/vuln-process /usr/src/vuln_process.c

# 設定 PHP 上傳容量限制與記憶體
RUN echo "upload_max_filesize = 50M\npost_max_size = 50M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

# 複製並設置 entrypoint 腳本以在啟動時自動修正目錄權限
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
