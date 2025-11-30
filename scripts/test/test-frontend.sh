#!/bin/bash

# Frontend 重啟與測試指南

echo "=========================================="
echo "CRM Frontend 重啟與測試"
echo "=========================================="
echo ""

# 檢查 Frontend 狀態
echo "1. 檢查 Frontend 狀態..."
FRONTEND_PID=$(ps aux | grep "nuxt dev" | grep "/home/jarvis/project/idea/as/crm/frontend" | grep -v grep | awk '{print $2}' | head -1)

if [ -n "$FRONTEND_PID" ]; then
  echo "   ✅ Frontend 正在運行 (PID: $FRONTEND_PID)"
  echo "   📍 URL: http://localhost:3000"
  echo ""
  
  # 檢查是否需要重啟
  echo "2. 檢查 nuxt.config.ts 修改時間..."
  CONFIG_TIME=$(stat -c %Y /home/jarvis/project/idea/as/crm/frontend/nuxt.config.ts 2>/dev/null || echo "0")
  PROCESS_TIME=$(ps -o lstart= -p $FRONTEND_PID | date -f - +%s 2>/dev/null || echo "0")
  
  if [ "$CONFIG_TIME" -gt "$PROCESS_TIME" ]; then
    echo "   ⚠️  nuxt.config.ts 已被修改，建議重啟"
    echo ""
    echo "   重啟步驟："
    echo "   1. 找到運行 'npm run dev' 的 terminal（可能是 pts/17）"
    echo "   2. 按 Ctrl+C 停止"
    echo "   3. 重新執行: npm run dev"
    echo ""
  else
    echo "   ℹ️  配置未變更，無需重啟"
    echo ""
  fi
else
  echo "   ❌ Frontend 未運行"
  echo ""
  echo "   啟動步驟："
  echo "   cd /home/jarvis/project/idea/as/crm/frontend"
  echo "   npm run dev"
  exit 1
fi

# 測試登入頁面
echo "3. 測試登入頁面..."
LOGIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000/login)

if [ "$LOGIN_STATUS" = "200" ]; then
  echo "   ✅ 登入頁面正常 (HTTP $LOGIN_STATUS)"
else
  echo "   ❌ 登入頁面異常 (HTTP $LOGIN_STATUS)"
fi
echo ""

# 顯示測試步驟
echo "=========================================="
echo "📝 完整測試流程"
echo "=========================================="
echo ""
echo "步驟 1: 開啟瀏覽器訪問登入頁面"
echo "   URL: http://localhost:3000/login"
echo ""
echo "步驟 2: 使用測試帳號登入"
echo "   使用者名稱: admin"
echo "   密碼: admin123"
echo ""
echo "步驟 3: 登入成功後訪問角色頁面"
echo "   URL: http://localhost:3000/roles"
echo ""
echo "步驟 4: 開啟瀏覽器開發者工具"
echo "   - 按 F12 開啟 DevTools"
echo "   - 切換到 Console 標籤"
echo "   - 查看 [useRoles] 開頭的日誌"
echo "   - 切換到 Network 標籤查看 API 請求"
echo ""
echo "預期結果:"
echo "   ✅ 看到角色列表（4個系統角色）"
echo "   ✅ Console 顯示成功日誌"
echo "   ✅ Network 顯示 200 狀態碼"
echo ""
echo "如果仍然失敗，請檢查:"
echo "   1. 瀏覽器 Console 的錯誤訊息"
echo "   2. Network 標籤的請求狀態和回應"
echo "   3. localStorage 是否有 crm_access_token"
echo ""
echo "=========================================="
echo "🛠️  調試命令"
echo "=========================================="
echo ""
echo "# 測試後端 API（需要先登入）"
echo "./test-roles-with-auth.sh"
echo ""
echo "# 檢查容器狀態"
echo "docker compose ps"
echo ""
echo "# 查看後端日誌"
echo "docker compose logs backend --tail 50"
echo ""
echo "=========================================="
