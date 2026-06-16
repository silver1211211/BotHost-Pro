FROM node:20-alpine

ARG BOTHOST_RUNTIME_SOURCE_HASH=unknown

ENV NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=8787 \
    BOTHOST_RUNTIME_SOURCE_HASH=${BOTHOST_RUNTIME_SOURCE_HASH}

LABEL com.bothost.runtime_source_hash=${BOTHOST_RUNTIME_SOURCE_HASH}

WORKDIR /app

COPY package*.json ./
RUN npm ci --omit=dev --ignore-scripts && npm cache clean --force

COPY server.js admin-helper-loader.js ./

RUN addgroup -S bothost && adduser -S bothost -G bothost
USER bothost

EXPOSE 8787

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:8787/health').then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))"

CMD ["node", "server.js"]
