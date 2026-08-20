#!/bin/bash
set -e

# 確保 uploads 目錄存在並設定適當擁有者與權限
mkdir -p /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 777 /var/www/html/public/uploads

# 產生預設大頭貼若不存在
if [ ! -f /var/www/html/public/uploads/default_avatar.svg ]; then
  cat << 'EOF' > /var/www/html/public/uploads/default_avatar.svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="120" height="120"><circle cx="50" cy="50" r="50" fill="#4f46e5"/><circle cx="50" cy="35" r="20" fill="#ffffff"/><path d="M 20 80 A 30 30 0 0 1 80 80 Z" fill="#ffffff"/></svg>
EOF
  chown www-data:www-data /var/www/html/public/uploads/default_avatar.svg
  chmod 666 /var/www/html/public/uploads/default_avatar.svg
fi

# 弱點版 .htaccess (Options +Indexes) 若不存在
if [ ! -f /var/www/html/public/uploads/.htaccess ]; then
  cat << 'EOF' > /var/www/html/public/uploads/.htaccess
# 弱點配置：故意開啟目錄瀏覽 (Directory Browsing)
Options +Indexes
EOF
  chown www-data:www-data /var/www/html/public/uploads/.htaccess
  chmod 666 /var/www/html/public/uploads/.htaccess
fi

# 執行容器後續指令 (預設為 apache2-foreground)
exec "$@"
