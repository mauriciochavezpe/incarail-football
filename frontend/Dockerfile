# syntax=docker/dockerfile:1

# 1) Build de la SPA con Vite.
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# La URL de la API queda fija en el build (Vite la inyecta en el bundle).
ARG VITE_API_URL=http://localhost:8000
ENV VITE_API_URL=${VITE_API_URL}
RUN npm run build

# 2) Archivos estáticos servidos por Nginx.
FROM nginx:1.28-alpine
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
HEALTHCHECK --interval=5s --timeout=3s --retries=10 CMD wget -qO- http://127.0.0.1/ >/dev/null || exit 1
EXPOSE 80
