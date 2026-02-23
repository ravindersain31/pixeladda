#!/usr/bin/env bash
php -d memory_limit=256M bin/console cache:clear

chmod 777 /var/www/html/var* -Rf

bin/console doctrine:schema:update --force
bin/console assets:install public --symlink

php-fpm -D && nginx -g "daemon off;"