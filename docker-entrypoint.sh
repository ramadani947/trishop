#!/bin/sh
set -e

PORT="${PORT:-8080}"

# Set port yang didengarkan Apache sesuai variabel PORT dari Railway.
sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
