#!/bin/bash

docker build --no-cache -f api/Dockerfile.prod -t running-tickets-api:latest ./api \
    && docker tag running-tickets-api:latest lu4nsds/running-tickets-api:latest \
    && docker push lu4nsds/running-tickets-api:latest
