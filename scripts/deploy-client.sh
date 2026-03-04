#!/bin/bash

docker compose exec client npm run build \
    && aws s3 sync client/dist/ s3://running-tickets-client --delete \
    && aws cloudfront create-invalidation --distribution-id E1J63U2V70PJKY --paths "/*" 1> /dev/null