#!/bin/sh
set -e

# Asegurar permisos de directorios que pueden ser montados como volúmenes.
chown -R www:www /var/www/storage /var/www/bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
