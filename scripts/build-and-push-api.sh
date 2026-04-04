#!/bin/bash

docker build --no-cache -f api/Dockerfile.prod -t 2ac5a56d-49dd-445b-a71d-65808ffa5f19:latest ./api \
    && docker tag 2ac5a56d-49dd-445b-a71d-65808ffa5f19:latest pedrohenrykes/2ac5a56d-49dd-445b-a71d-65808ffa5f19:latest \
    && docker push pedrohenrykes/2ac5a56d-49dd-445b-a71d-65808ffa5f19:latest
