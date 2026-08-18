set -e

echo "===> [DEBUG] Préparation certificat SSL MySQL"
mkdir -p storage/certs
if [ -n "$MYSQL_CA_CERT" ]; then
  echo "$MYSQL_CA_CERT" > storage/certs/aiven-ca.pem
  chmod 644 storage/certs/aiven-ca.pem
  echo "===> [DEBUG] Certificat écrit — taille : $(wc -c < storage/certs/aiven-ca.pem) octets"
  head -1 storage/certs/aiven-ca.pem
else
  echo "===> [DEBUG] ⚠️  MYSQL_CA_CERT est VIDE ou absent"
fi
echo "===> [DEBUG] Fin bloc certificat"
# ... reste du script existant
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
chmod -R 775 storage bootstrap/cache

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

# Droits (Reinforced just before start)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "===> Lancement de Supervisor"

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
