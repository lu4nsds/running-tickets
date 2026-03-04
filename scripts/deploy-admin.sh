#!/bin/bash

docker compose exec admin npm run build \
    && aws s3 sync admin/dist/ s3://running-tickets-admin --delete \
    && aws cloudfront create-invalidation --distribution-id E2LL2HOM9X8OZV --paths "/*" 1> /dev/null