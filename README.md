# rest_api
#DATABASE SETUP FOR UBUNTU SETUP
1.Create user commands.

    sudo mysql -e "create user nginx@localhost identified via unix_socket;"
2. Create database.
    sudo mysql -e "create database helpline"
3. Import database schema.
    sudo  mysql helpline < /usr/src/OpenChs/rest_api/uchl.sql
4. Grant authorization.
    grant select,insert on tower.* to nginx@localhost;
    grant update on helpline.auth to nginx@localhost;
    grant update on helpline.contact to nginx@localhost;
    grant update on helpline.kase to nginx@localhost;
    grant update on helpline.kase_activity to nginx@localhost;
    grant update on helpline.activity to nginx@localhost;
    grant update on helpline.disposition to nginx@localhost;
    grant delete on helpline.session to nginx@localhost;
    grant update on helpline.chan to nginx@localhost;
5. Database setup complete.
    exit;

#Server Setup For Ubuntu Linux
1. install nginx
sudo apt-get install nginx

2. Configure nginx 
  * Edit the file /etc/nginx/nginx.conf
       * add the following server block
         server {
        listen       443 ssl;
        server_name  _;
        root         /var/www/html;

        ssl_certificate "/etc/pki/openchs/openchs.crt";
        ssl_certificate_key "/etc/pki/openchs/private/openchs.key";
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_prefer_server_ciphers on;
        ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';


        # Load configuration files for the default server block.
        include /etc/nginx/default.d/*.conf;

        location / {
        }

        error_page 404 /404.html;
            location = /40x.html {
        }

        error_page 500 502 503 504 /50x.html;
            location = /50x.html {
        }


        location /helpline/{
                index index.php index.html index.htm;
                try_files $uri $uri/ /helpline/api/index.php?$args;
        }
    }

    server {
        listen     8384 ssl;
        ssl_certificate "/etc/pki/openchs/openchs.crt";
        ssl_certificate_key "/etc/pki/openchs/private/openchs.key";
        location / {
                proxy_read_timeout              300s;
                proxy_http_version              1.1;
                proxy_set_header Host           $host;
                proxy_set_header Connection     $http_connection; #"Upgrade";
                proxy_set_header Upgrade        $http_upgrade;
                proxy_set_header XCLIENTIP      $remote_addr;
                proxy_pass                      http://127.0.0.1:8383/;
        }
    }
   
    }
  
3. create a file /etc/nginx/conf.d/php-fpm.conf
     * Edit the file add the following content
          PHP-FPM FastCGI server
         # network or unix domain socket configuration

        upstream php-fpm {
        server unix:/run/php/php-fpm.sock;
      }
4.  create a file  /etc/nginx/default.d/php.conf
     # pass the PHP scripts to FastCGI server
     #
     # See conf.d/php-fpm.conf for socket configuration
     #
     index index.php index.html index.htm;

    location ~ \.(php|phar)(/.*)?$ {
    fastcgi_split_path_info ^(.+\.(?:php|phar))(/.*)$;

    fastcgi_intercept_errors on;
    fastcgi_index  index.php;
    include        fastcgi_params;
    fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
    fastcgi_param  PATH_INFO $fastcgi_path_info;
    fastcgi_pass   php-fpm;
    }






#REST API Setup For Ubuntu Linux
1. install php
      sudo apt-get install php
2. install php-mysql
      sudo apt-get install php-mysql
3. install php-fpm
     sudo apt-get install php-fpm
4. Edit /etc/php-fpm file 
     * uncomment line
       error_log = /var/log/php8.2-fpm.log
5. Edit /etc/php/8.2/fpm/pool.d/www.conf
      * uncomment the following line
         php_admin_value[error_log] = /var/log/fpm-php.www.log
         php_admin_flag[log_errors] = on
     * add or edit the following line
         listen = /run/php/php8.2-fpm.sock

     * change the user and  group to nginx in the following lines.
         listen.owner = nginx
         listen.group = nginx
         user = nginx
         group = nginx





#UI Setup for Ubuntu Server

1. copy files from your /application 
   
      cp  -r /home/"app_files where you cloned your app/* /var/www/html/helpline/.


