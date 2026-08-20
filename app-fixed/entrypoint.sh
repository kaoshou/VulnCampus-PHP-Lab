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

# 安全修復版 .htaccess (Options -Indexes 與禁止解析 PHP 腳本)
cat << 'EOF' > /var/www/html/public/uploads/.htaccess
# 安全防禦配置：強制關閉目錄瀏覽 (Disable Directory Browsing)
Options -Indexes

# 縱深防禦：禁止在上傳資料夾內解析與執行任何 PHP 腳本，防範 WebShell RCE 攻擊
<FilesMatch "\.(php|php5|php8|phtml)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
EOF
chown www-data:www-data /var/www/html/public/uploads/.htaccess
chmod 666 /var/www/html/public/uploads/.htaccess

# 執行容器後續指令 (預設為 apache2-foreground)
exec "$@"
