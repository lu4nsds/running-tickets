#!/bin/sh

# =============================================================================
# Entrypoint de produção
# Executa otimizações do Laravel antes de iniciar o php-fpm
# =============================================================================

echo "[entrypoint] Limpando caches anteriores..."
php artisan config:clear --quiet &> /dev/null
php artisan route:clear --quiet &> /dev/null
php artisan view:clear --quiet &> /dev/null

echo "[entrypoint] Gerando caches de produção..."
php artisan config:cache --quiet &> /dev/null
php artisan route:cache --quiet &> /dev/null
php artisan view:cache --quiet &> /dev/null

echo "[entrypoint] Criando link simbólico de storage (se necessário)..."
php artisan storage:link --quiet &> /dev/null

if [ "$RAILWAY_SERVICE_NAME" = "runningtickets-api" ]; then
    echo "[entrypoint] Executando migrations..."
    php artisan migrate --force --no-interaction
fi

echo "[entrypoint] Adicionando porta $PORT à config do nginx"
envsubst '$PORT' < /etc/nginx/http.d/default.conf > /tmp/default.conf
mv /tmp/default.conf /etc/nginx/http.d/default.conf

echo "[entrypoint] Executando comando padrão do container: $*"
exec "$@"
