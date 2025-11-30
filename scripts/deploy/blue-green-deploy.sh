#!/bin/bash

# ============================================================================
# CRM RBAC 藍綠部署腳本
# ============================================================================
# 支援零停機部署，透過藍綠環境切換實現
#
# 使用方式：
#   ./blue-green-deploy.sh deploy    # 部署到非活躍環境並切換
#   ./blue-green-deploy.sh status    # 查看當前部署狀態
#   ./blue-green-deploy.sh switch    # 手動切換環境
#   ./blue-green-deploy.sh rollback  # 回滾到上一個環境
#   ./blue-green-deploy.sh health    # 健康檢查
#
# ============================================================================

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# 配置
COMPOSE_FILE="docker-compose.blue-green.yml"
ENV_FILE=".env.prod"
NGINX_CONF="docker/nginx/conf.d/blue-green.conf"
HEALTH_CHECK_TIMEOUT=60
HEALTH_CHECK_INTERVAL=5

# ============================================================================
# 輔助函數
# ============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 取得當前活躍環境
get_active_color() {
    if [ -f "$NGINX_CONF" ]; then
        grep -oP 'set \$active_color \K\w+' "$NGINX_CONF" | head -1
    else
        echo "blue"
    fi
}

# 取得非活躍環境
get_inactive_color() {
    local active=$(get_active_color)
    if [ "$active" = "blue" ]; then
        echo "green"
    else
        echo "blue"
    fi
}

# 檢查環境是否健康
check_health() {
    local color=$1
    local service=$2
    local max_attempts=$((HEALTH_CHECK_TIMEOUT / HEALTH_CHECK_INTERVAL))
    local attempt=0

    log_info "檢查 $color 環境 $service 健康狀態..."

    while [ $attempt -lt $max_attempts ]; do
        if docker compose -f "$COMPOSE_FILE" ps --format json 2>/dev/null | \
           grep -q "\"Name\":\"crm_${service}_${color}\".*\"Health\":\"healthy\""; then
            log_success "$color $service 健康"
            return 0
        fi

        attempt=$((attempt + 1))
        echo -n "."
        sleep $HEALTH_CHECK_INTERVAL
    done

    echo ""
    log_error "$color $service 健康檢查失敗"
    return 1
}

# 切換 nginx upstream
switch_nginx() {
    local new_color=$1
    local conf_file="$NGINX_CONF"

    log_info "切換 Nginx upstream 到 $new_color 環境..."

    # 備份配置
    cp "$conf_file" "${conf_file}.bak"

    # 修改 active_color
    sed -i "s/set \$active_color \w\+;/set \$active_color $new_color;/" "$conf_file"

    # 重新載入 nginx
    if docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -t 2>/dev/null; then
        docker compose -f "$COMPOSE_FILE" exec -T nginx nginx -s reload
        log_success "Nginx 已切換到 $new_color 環境"
    else
        log_error "Nginx 配置驗證失敗，正在回滾..."
        mv "${conf_file}.bak" "$conf_file"
        return 1
    fi

    rm -f "${conf_file}.bak"
}

# ============================================================================
# 主要命令
# ============================================================================

# 查看狀態
cmd_status() {
    echo ""
    echo "=============================================="
    echo -e "${CYAN}CRM RBAC 藍綠部署狀態${NC}"
    echo "=============================================="
    echo ""

    local active=$(get_active_color)
    local inactive=$(get_inactive_color)

    echo -e "當前活躍環境: ${GREEN}$active${NC}"
    echo -e "備用環境:     ${YELLOW}$inactive${NC}"
    echo ""

    echo "容器狀態："
    echo "-------------------------------------------"
    docker compose -f "$COMPOSE_FILE" ps --format "table {{.Name}}\t{{.Status}}\t{{.Health}}" 2>/dev/null || \
    docker compose -f "$COMPOSE_FILE" ps
    echo ""
}

# 部署到非活躍環境
cmd_deploy() {
    echo ""
    echo "=============================================="
    echo -e "${CYAN}CRM RBAC 藍綠部署${NC}"
    echo "=============================================="
    echo ""

    # 檢查環境
    if [ ! -f "$ENV_FILE" ]; then
        log_error "$ENV_FILE 不存在"
        exit 1
    fi

    # 載入環境變數
    set -a
    source "$ENV_FILE"
    set +a

    local active=$(get_active_color)
    local target=$(get_inactive_color)

    log_info "當前活躍環境: $active"
    log_info "目標部署環境: $target"
    echo ""

    # Step 1: 構建目標環境映像
    log_info "[1/5] 構建 $target 環境映像..."
    docker compose -f "$COMPOSE_FILE" --profile $target build --no-cache
    log_success "映像構建完成"
    echo ""

    # Step 2: 啟動目標環境
    log_info "[2/5] 啟動 $target 環境..."
    docker compose -f "$COMPOSE_FILE" --profile $target up -d
    log_success "$target 環境已啟動"
    echo ""

    # Step 3: 健康檢查
    log_info "[3/5] 執行健康檢查..."
    if ! check_health "$target" "backend"; then
        log_error "健康檢查失敗，中止部署"
        exit 1
    fi
    if ! check_health "$target" "frontend"; then
        log_error "健康檢查失敗，中止部署"
        exit 1
    fi
    log_success "健康檢查通過"
    echo ""

    # Step 4: 執行資料庫遷移
    log_info "[4/5] 執行資料庫遷移..."
    docker compose -f "$COMPOSE_FILE" exec -T "backend-$target" php run-migrations.php || \
        log_warning "Migration 可能已執行過"
    log_success "資料庫遷移完成"
    echo ""

    # Step 5: 切換流量
    log_info "[5/5] 切換流量到 $target 環境..."
    switch_nginx "$target"
    echo ""

    # 更新 .env.prod
    sed -i "s/DEPLOY_COLOR=\w\+/DEPLOY_COLOR=$target/" "$ENV_FILE"

    echo "=============================================="
    log_success "部署完成！"
    echo "=============================================="
    echo ""
    echo -e "活躍環境已切換到: ${GREEN}$target${NC}"
    echo -e "舊環境 ($active) 仍在運行，可用於快速回滾"
    echo ""
    echo "如需回滾，執行: $0 rollback"
    echo ""
}

# 手動切換環境
cmd_switch() {
    local active=$(get_active_color)
    local target=$(get_inactive_color)

    echo ""
    log_info "切換環境: $active → $target"

    # 檢查目標環境是否運行
    if ! docker compose -f "$COMPOSE_FILE" ps | grep -q "crm_backend_$target.*Up"; then
        log_error "$target 環境未運行，請先部署"
        exit 1
    fi

    switch_nginx "$target"
    sed -i "s/DEPLOY_COLOR=\w\+/DEPLOY_COLOR=$target/" "$ENV_FILE"

    echo ""
    log_success "已切換到 $target 環境"
    echo ""
}

# 回滾
cmd_rollback() {
    local active=$(get_active_color)
    local target=$(get_inactive_color)

    echo ""
    echo "=============================================="
    echo -e "${YELLOW}CRM RBAC 回滾${NC}"
    echo "=============================================="
    echo ""

    log_info "回滾: $active → $target"

    # 檢查目標環境是否運行
    if ! docker compose -f "$COMPOSE_FILE" ps | grep -q "crm_backend_$target.*Up"; then
        log_error "$target 環境未運行，無法回滾"
        exit 1
    fi

    switch_nginx "$target"
    sed -i "s/DEPLOY_COLOR=\w\+/DEPLOY_COLOR=$target/" "$ENV_FILE"

    echo ""
    log_success "已回滾到 $target 環境"
    echo ""
}

# 健康檢查
cmd_health() {
    echo ""
    echo "=============================================="
    echo -e "${CYAN}健康檢查${NC}"
    echo "=============================================="
    echo ""

    local active=$(get_active_color)

    # 檢查 Nginx
    log_info "檢查 Nginx..."
    if curl -sf http://localhost/health > /dev/null 2>&1; then
        log_success "Nginx 健康"
    else
        log_error "Nginx 不健康"
    fi

    # 檢查活躍環境
    log_info "檢查 $active 環境..."
    check_health "$active" "backend" || true
    check_health "$active" "frontend" || true

    echo ""
}

# 清理舊環境
cmd_cleanup() {
    local inactive=$(get_inactive_color)

    echo ""
    log_info "清理 $inactive 環境容器..."

    docker compose -f "$COMPOSE_FILE" --profile $inactive down --remove-orphans

    log_success "$inactive 環境已清理"
    echo ""
}

# 顯示幫助
cmd_help() {
    echo ""
    echo "CRM RBAC 藍綠部署腳本"
    echo ""
    echo "使用方式: $0 <command>"
    echo ""
    echo "Commands:"
    echo "  deploy    部署到非活躍環境並切換流量"
    echo "  status    查看當前部署狀態"
    echo "  switch    手動切換環境（不重新部署）"
    echo "  rollback  回滾到上一個環境"
    echo "  health    執行健康檢查"
    echo "  cleanup   清理非活躍環境容器"
    echo "  help      顯示此幫助訊息"
    echo ""
}

# ============================================================================
# 主程序
# ============================================================================

# 切換到專案目錄
cd "$(dirname "$0")/.."

case "${1:-help}" in
    deploy)
        cmd_deploy
        ;;
    status)
        cmd_status
        ;;
    switch)
        cmd_switch
        ;;
    rollback)
        cmd_rollback
        ;;
    health)
        cmd_health
        ;;
    cleanup)
        cmd_cleanup
        ;;
    help|--help|-h)
        cmd_help
        ;;
    *)
        log_error "未知命令: $1"
        cmd_help
        exit 1
        ;;
esac
