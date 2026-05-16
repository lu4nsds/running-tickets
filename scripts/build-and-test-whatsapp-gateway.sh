#!/bin/bash

docker build --debug -f whatsapp-gateway/Dockerfile.prod -t running-tickets-whatsapp-gateway:latest ./whatsapp-gateway \
    && docker run --rm \
            --name running-tickets-whatsapp-gateway-production \
            --env-file ./whatsapp-gateway/.env \
            --network running-tickets-network \
            -p 3000:3000 \
            running-tickets-whatsapp-gateway:latest
