FROM node:20-alpine

WORKDIR /app

# Dependências de build
RUN apk add --no-cache python3 make g++ git

COPY package*.json ./
RUN npm install

COPY tsconfig.json ./
COPY src ./src

RUN npm run build

EXPOSE 3000

CMD ["node", "dist/main.js"]
