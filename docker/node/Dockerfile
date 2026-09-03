FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./
COPY tsconfig.json ./
COPY resources ./resources

RUN npm ci

EXPOSE 5173

CMD ["npm", "run", "dev"]
