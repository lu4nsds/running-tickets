#!/bin/bash

# Script para criar usuários de teste no Mercado Pago
# Substitua YOUR_PRODUCTION_ACCESS_TOKEN pela sua credencial de produção

PROD_TOKEN="APP_USR-3185262056130153-012922-d733ad21505f630554ff39d01dd16709-3169176614"

echo "Criando VENDEDOR de teste..."
curl -X POST \
  https://api.mercadopago.com/users/test_user \
  -H "Authorization: Bearer $PROD_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "site_id": "MLB"
  }'

echo -e "\n\nCriando COMPRADOR de teste..."
curl -X POST \
  https://api.mercadopago.com/users/test_user \
  -H "Authorization: Bearer $PROD_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "site_id": "MLB"
  }'
