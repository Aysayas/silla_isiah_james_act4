#!/bin/sh
set -e
# Render assigns a random $PORT at runtime; bake it into nginx.conf before boot.
sed -i "s/listen 80;/listen ${PORT:-10000};/" /etc/nginx/http.d/default.conf
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
