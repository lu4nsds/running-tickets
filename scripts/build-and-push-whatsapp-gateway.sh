#!/bin/bash

cat ~/.docker/tokens/docker.io-pedrohenrykes.token | docker login -u pedrohenrykes --password-stdin \
    && docker build --no-cache -f whatsapp-gateway/Dockerfile.prod -t 87f468bf-b4f8-4dc2-92e2-0c95a1bf967e:latest ./whatsapp-gateway \
    && docker tag 87f468bf-b4f8-4dc2-92e2-0c95a1bf967e:latest pedrohenrykes/87f468bf-b4f8-4dc2-92e2-0c95a1bf967e:latest \
    && docker push pedrohenrykes/87f468bf-b4f8-4dc2-92e2-0c95a1bf967e:latest
