#!/bin/sh

# =============================================================================
# Entrypoint de produção
# Executa otimizações do Laravel antes de iniciar o php-fpm
# =============================================================================

set -e

echo "[entrypoint] Limpando caches anteriores..."
php artisan config:clear &> /dev/null
php artisan route:clear &> /dev/null
php artisan view:clear &> /dev/null

echo "[entrypoint] Gerando caches de produção..."
php artisan config:cache &> /dev/null
php artisan route:cache &> /dev/null
php artisan view:cache &> /dev/null

echo "[entrypoint] Criando link simbólico de storage (se necessário)..."
php artisan storage:link &> /dev/null

echo "[entrypoint] Adicionando porta $PORT à config do nginx"
envsubst '$PORT' < /etc/nginx/http.d/default.conf > /tmp/default.conf
mv /tmp/default.conf /etc/nginx/http.d/default.conf

echo "[entrypoint] Executando comando padrão do container: $*"
exec "$@"
