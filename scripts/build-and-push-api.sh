#!/bin/bash

docker build --no-cache -f api/Dockerfile.prod -t running-tickets-api:latest ./api \
    && docker tag running-tickets-api:latest pedrohenrykes/running-tickets-api:latest \
    && docker push pedrohenrykes/running-tickets-api:latest
