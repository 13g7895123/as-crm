# ============================================
# Frontend Production Dockerfile - Multi-stage Build
# ============================================
# Stage 1: Build Stage (依賴安裝 + 建置)
# Stage 2: Production Stage (僅包含產出檔案)

# ============================================
# Stage 1: Build Stage
# ============================================
FROM node:20-alpine AS builder

LABEL maintainer="CRM RBAC Team"
LABEL description="CRM RBAC Frontend Build Stage"

# 安裝建置所需工具
RUN apk add --no-cache \
    git \
    python3 \
    make \
    g++

# 設定工作目錄
WORKDIR /app

# 複製 package 檔案
COPY package*.json ./

# 安裝所有依賴（包含 devDependencies 以進行建置）
RUN npm install --no-audit --legacy-peer-deps

# 複製應用程式檔案
COPY . .

# 建置 Nuxt 應用程式（產生 .output 目錄）
RUN npm run build

# ============================================
# Stage 2: Production Stage
# ============================================
FROM node:20-alpine

LABEL maintainer="CRM RBAC Team"
LABEL description="CRM RBAC Frontend Production Environment"

# 安裝執行時所需的最小套件
RUN apk add --no-cache \
    dumb-init \
    wget

# 建立非特權使用者
RUN addgroup -g 1001 -S nodejs \
    && adduser -S nuxt -u 1001

# 設定工作目錄
WORKDIR /app

# 從 builder stage 複製產出檔案
COPY --from=builder --chown=nuxt:nodejs /app/.output ./.output

# 切換到非特權使用者
USER nuxt

# 開放 3000 port
EXPOSE 3000

# 設定環境變數
ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000
ENV NUXT_TELEMETRY_DISABLED=1

# 健康檢查
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD wget -q --spider http://localhost:3000/ || exit 1

# 使用 dumb-init 啟動應用程式（正確處理訊號）
ENTRYPOINT ["/usr/bin/dumb-init", "--"]

# 啟動 Nuxt 生產伺服器
CMD ["node", ".output/server/index.mjs"]
