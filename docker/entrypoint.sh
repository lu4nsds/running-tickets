#!/bin/bash

# Aguardar o banco de dados estar pronto
echo "Aguardando banco de dados..."
sleep 5

# Instalar dependências do Composer se não existirem
if [ ! -d "/var/www/vendor" ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --optimize-autoloader
fi

# Instalar dependências do NPM se não existirem
if [ ! -d "/var/www/node_modules" ]; then
    echo "Instalando dependências do NPM..."
    npm install
fi

# Gerar chave da aplicação se não existir
if grep -q "APP_KEY=$" /var/www/.env; then
    echo "Gerando chave da aplicação..."
    php artisan key:generate
fi

# Executar migrations
echo "Executando migrations..."
php artisan migrate --force

# Limpar e cachear configurações
echo "Otimizando aplicação..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Aplicação iniciada!"

# Executar o comando passado como argumento
exec "$@"
