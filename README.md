# rest_api
REST API Setup on the Server
Database Setup
Create User:

bash
Copy
Edit
sudo mysql -e "CREATE USER 'nginx'@'localhost' IDENTIFIED VIA unix_socket;"
Create Database:

bash
Copy
Edit
sudo mysql -e "CREATE DATABASE helpline;"
Import Database Schema:

bash
Copy
Edit
sudo mysql helpline < /usr/src/OpenChs/rest_api/uchl.sql
Grant Permissions:

bash
Copy
Edit
sudo mysql -e "
GRANT SELECT, INSERT ON tower.* TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.auth TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.contact TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.kase TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.kase_activity TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.activity TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.disposition TO 'nginx'@'localhost';
GRANT DELETE ON helpline.session TO 'nginx'@'localhost';
GRANT UPDATE ON helpline.chan TO 'nginx'@'localhost';
FLUSH PRIVILEGES;"
Exit MySQL:

bash
Copy
Edit
exit
Server Setup
Install Nginx:

bash
Copy
Edit
sudo apt-get install nginx
Configure Nginx:

Edit the file /etc/nginx/nginx.conf and add the following server blocks:

nginx
Copy
Edit
server {
    listen       443 ssl;
    server_name  _;
    root         /var/www/html;

    ssl_certificate "/etc/pki/openchs/openchs.crt";
    ssl_certificate_key "/etc/pki/openchs/private/openchs.key";
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';

    location / {
    }

    error_page 404 /404.html;
    location = /40x.html {}

    error_page 500 502 503 504 /50x.html;
    location = /50x.html {}

    location /helpline/ {
        index index.php index.html index.htm;
        try_files $uri $uri/ /helpline/api/index.php?$args;
    }
}

server {
    listen     8384 ssl;
    ssl_certificate "/etc/pki/openchs/openchs.crt";
    ssl_certificate_key "/etc/pki/openchs/private/openchs.key";
    location / {
        proxy_read_timeout 300s;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header Connection $http_connection;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header XCLIENTIP $remote_addr;
        proxy_pass http://127.0.0.1:8383/;
    }
}
PHP-FPM Configuration:

Create /etc/nginx/conf.d/php-fpm.conf:

nginx
Copy
Edit
upstream php-fpm {
    server unix:/run/php/php8.2-fpm.sock;
}
Create /etc/nginx/default.d/php.conf:

nginx
Copy
Edit
index index.php index.html index.htm;

location ~ \.(php|phar)(/.*)?$ {
    fastcgi_split_path_info ^(.+\.(?:php|phar))(/.*)$;
    fastcgi_intercept_errors on;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param PATH_INFO $fastcgi_path_info;
    fastcgi_pass php-fpm;
}
REST API Setup
Install PHP:

bash
Copy
Edit
sudo apt-get install php
Install PHP-MySQL:

bash
Copy
Edit
sudo apt-get install php-mysql
Install PHP-FPM:

bash
Copy
Edit
sudo apt-get install php-fpm
Configure PHP-FPM:

Edit /etc/php/8.2/fpm/php-fpm.conf:

ini
Copy
Edit
error_log = /var/log/php8.2-fpm.log
Edit /etc/php/8.2/fpm/pool.d/www.conf:

ini
Copy
Edit
php_admin_value[error_log] = /var/log/fpm-php.www.log
php_admin_flag[log_errors] = on
listen = /run/php/php8.2-fpm.sock
listen.owner = nginx
listen.group = nginx
user = nginx
group = nginx
UI Setup
Copy Application Files:
bash
Copy
Edit
cp -r /home/<app_files_directory>/* /var/www/html/helpline/
