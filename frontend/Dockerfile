# ============ Dependencies ============
FROM node:20-alpine AS deps
RUN apk add --no-cache libc6-compat
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

# ============ Builder ============
FROM node:20-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

ENV NEXT_TELEMETRY_DISABLED 1
ENV NODE_ENV production

ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL

RUN npm run build

# ============ Test ============
# Etapa auxiliar para correr Jest en Docker (target: test).
FROM node:20-alpine AS test
RUN apk add --no-cache libc6-compat
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .

CMD ["npm", "test"]

# ============ Development ============
# Servidor de desarrollo con hot reload (target: dev).
# docker-compose.override.yml monta ./src y ./public del host sobre
# /app/src y /app/public: al guardar un archivo (Ctrl+S), next dev
# recompila solo lo tocado y el navegador se actualiza solo (HMR).
# next dev escucha por defecto en 0.0.0.0 (verificado en los docs de
# Next 16 incluídos), así que el mapeo de puertos de Docker funciona
# sin flags extra.
FROM node:20-alpine AS dev
RUN apk add --no-cache libc6-compat
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

# Source base para el build inicial; el bind mount del override lo pisa
# en runtime, por eso editar en el host no requiere reconstruir la imagen.
COPY . .

ENV NODE_ENV=development
EXPOSE 3000

CMD ["npm", "run", "dev"]

# ============ Runner ============
FROM node:20-alpine AS runner
WORKDIR /app

ENV NODE_ENV production
ENV NEXT_TELEMETRY_DISABLED 1

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

RUN chown -R nextjs:nodejs /app

USER nextjs

EXPOSE 3000

ENV PORT 3000
ENV HOSTNAME "0.0.0.0"

CMD ["node", "server.js"]
