# crm Development Guidelines

Auto-generated from all feature plans. Last updated: 2025-10-21

## Active Technologies
- (001-rbac-permission-management)

## Project Structure
```
backend/
frontend/
tests/
```

## Commands

### 開發環境服務
- **Backend API**: http://localhost:9230
- **Frontend**: http://localhost:9330
- **phpMyAdmin**: http://localhost:9730 (資料庫管理工具)
  - 使用者名稱：crm_user
  - 密碼：crm_password
- **MariaDB**: localhost:9130

### Docker 指令
```bash
# 啟動所有服務
docker compose up -d

# 查看服務狀態
docker compose ps

# 停止所有服務
docker compose down
```

## Code Style
: Follow standard conventions

## Recent Changes
- 2025-10-24: 修改 build-backend-dev.sh 加入 phpMyAdmin
  - 在後端開發環境啟動腳本中加入 phpmyadmin 服務
  - 更新服務資訊輸出，顯示 phpMyAdmin URL 和登入資訊
  - 更新常用指令，反映 phpmyadmin 服務的管理
- 2025-10-24: 修復 RoleSeeder 外鍵約束問題
  - **問題**：RoleSeeder 需要 `created_by=1` 的用戶，但 users 表是空的
  - **解決方案**：
    - 創建 UserSeeder 建立系統管理員（ID=1）
    - 調整 run-seeders.php 執行順序：UserSeeder → RoleSeeder → PermissionSeeder
    - 為所有 seeders 添加重複檢查，避免重複執行時出錯
  - **系統管理員帳號**：
    - 帳號：admin
    - 密碼：admin123
    - ⚠️ 請在生產環境中立即修改預設密碼！
- 2025-10-24: 修復 run-migrations.php Array to string 警告
  - 修正 `getCliMessages()` 返回數組的處理方式
  - 使用 `implode()` 將消息數組正確格式化輸出
- 2025-10-24: 修復容器重啟問題並改進資料庫操作方式
  - **問題**：`php spark` CLI 命令因全局 HTTP filters 干擾而無法正常運行
  - **解決方案**：
    - 回退到使用 PHP 內建伺服器 (`php -S`) 啟動 HTTP 服務
    - 新增 `run-seeders.php` 腳本用於執行 database seeders
    - 使用 `run-migrations.php` 和 `run-seeders.php` 繞過 spark CLI 限制
    - 在所有全局 filters 中添加 `is_cli()` 檢查，防止 HTTP filters 干擾 CLI 請求
  - **使用方式**：
    - Migrations: `docker compose exec backend php run-migrations.php`
    - Seeders: `docker compose exec backend php run-seeders.php`
- 2025-10-24: 修復 build-backend-dev.sh chmod 權限錯誤
  - 在啟動容器前停止現有容器並清理舊日誌檔案
- 2025-10-24: 新增 phpMyAdmin 服務至 Docker Compose，port 統一於 .env 管理
- 001-rbac-permission-management: Added

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
