# ---------------------------------------------------------------------------
# Development: Vite dev server with hot reload (source bind-mounted by Compose)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS development

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

EXPOSE 5173
CMD ["npm", "run", "dev"]

# ---------------------------------------------------------------------------
# Build: compile the SPA
# ---------------------------------------------------------------------------
FROM development AS build

RUN npm run build

# ---------------------------------------------------------------------------
# Production: Nginx serves the SPA and proxies /api to the backend service
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS production

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
