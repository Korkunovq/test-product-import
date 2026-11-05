#!/bin/sh
set -e
php bin/console migrations:migrate || true
exec php-fpm
