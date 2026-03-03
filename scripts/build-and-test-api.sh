#!/bin/bash

docker build --debug -f api/Dockerfile.prod -t running-tickets-api:latest ./api \
    && docker tag running-tickets-api:latest pedrohenrykes/running-tickets-api:latest \
    && docker run --rm \
            --name running-tickets-api-production \
            --env-file ./api/.env \
            --network running-tickets_running-tickets-network \
            -p 8080:8080 \
            running-tickets-api:latest
