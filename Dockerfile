FROM php:8.2-fpm-alpine

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apk add --no-cache nginx

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

COPY <<'EOF' /etc/nginx/http.d/default.conf
server {
    listen 80;
    root /var/www/html;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

COPY <<'EOF' /start.sh
#!/bin/sh
php-fpm -D
nginx -g "daemon off;"
EOF

RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
