#!/bin/sh
set -e

echo "===> Démarrage Laravel"

cd /var/www/html

# S'assurer qu'un fichier .env existe
if [ ! -f .env ]; then
    echo "Fichier .env manquant, création à partir de .env.example"
    cp .env.example .env
fi

# Créer les dossiers si besoin
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache

# Droits
chown -R www-data:www-data storage bootstrap/cache

# Générer la clé seulement si absente
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY absente : génération..."
    php artisan key:generate --force
fi

# Optimisation (ne bloque pas le démarrage)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Migration (ne bloque pas si la DB n'est pas encore prête)
php artisan migrate --force || true

echo "===> Lancement de Supervisor"

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
