FROM php:8.4-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

# Limpa cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instala redis
RUN pecl install redis && docker-php-ext-enable redis

# Obtem o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Substitui as configurações do Docker para suprimir o registro de acesso.
COPY .docker/docker.conf /usr/local/etc/php-fpm.d/docker.conf

# Define diretório de trabalho
WORKDIR /var/www

# Expõe porta 9000
EXPOSE 9000

# Inicia servidor php-fpm
CMD ["php-fpm"]
