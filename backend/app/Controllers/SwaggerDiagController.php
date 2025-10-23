<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Swagger Diagnostics Controller
 * Helps debug Swagger UI issues
 */
class SwaggerDiagController extends Controller
{
    public function index()
    {
        $checks = [];

        // Check 1: OpenAPI spec file exists
        $specPath = ROOTPATH . 'docs/openapi.yaml';
        $checks['spec_file_exists'] = file_exists($specPath);
        $checks['spec_file_path'] = $specPath;

        // Check 2: Spec file is readable
        if ($checks['spec_file_exists']) {
            $checks['spec_file_readable'] = is_readable($specPath);
            $checks['spec_file_size'] = filesize($specPath);
        } else {
            $checks['spec_file_readable'] = false;
            $checks['spec_file_size'] = 0;
        }

        // Check 3: Test spec endpoint
        $checks['spec_endpoint'] = '/swagger/spec';

        // Check 4: Test CDN resources
        $checks['cdn_css'] = 'https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css';
        $checks['cdn_js_bundle'] = 'https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js';
        $checks['cdn_js_preset'] = 'https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js';

        // Check 5: Server info
        $checks['base_url'] = base_url();
        $checks['site_url'] = site_url();
        $checks['server_port'] = $_SERVER['SERVER_PORT'] ?? 'unknown';
        $checks['http_host'] = $_SERVER['HTTP_HOST'] ?? 'unknown';

        // Generate diagnostic HTML
        $html = $this->generateDiagHTML($checks);

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($html);
    }

    private function generateDiagHTML($checks)
    {
        $specExists = $checks['spec_file_exists'] ? '✅' : '❌';
        $specReadable = ($checks['spec_file_readable'] ?? false) ? '✅' : '❌';
        $failClass = ($specExists === '❌') ? 'fail' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swagger 診斷工具</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        .check {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        .check.fail {
            border-left-color: #f44336;
        }
        .status {
            font-size: 24px;
            margin-right: 10px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
        .test-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .test-btn:hover {
            background: #0b7dda;
        }
        #test-results {
            margin-top: 20px;
        }
        .result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
        }
        .code {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Swagger UI 診斷工具</h1>

    <h2>系統檢查</h2>

    <div class="check {$failClass}">
        <span class="status">{$specExists}</span>
        <strong>OpenAPI 規範文件</strong>
        <p>路徑: <code>{$checks['spec_file_path']}</code></p>
        <p>可讀: {$specReadable}</p>
        <p>大小: {$checks['spec_file_size']} bytes</p>
    </div>

    <div class="check">
        <span class="status">ℹ️</span>
        <strong>服務器信息</strong>
        <p>Base URL: <code>{$checks['base_url']}</code></p>
        <p>Site URL: <code>{$checks['site_url']}</code></p>
        <p>HTTP Host: <code>{$checks['http_host']}</code></p>
        <p>Server Port: <code>{$checks['server_port']}</code></p>
    </div>

    <h2>端點測試</h2>

    <div>
        <button class="test-btn" onclick="testEndpoint('/swagger', 'Swagger UI')">測試 Swagger UI</button>
        <button class="test-btn" onclick="testEndpoint('/swagger/spec', 'OpenAPI Spec')">測試 OpenAPI Spec</button>
        <button class="test-btn" onclick="testCDN('{$checks['cdn_css']}', 'CSS')">測試 CDN CSS</button>
        <button class="test-btn" onclick="testCDN('{$checks['cdn_js_bundle']}', 'JS Bundle')">測試 CDN JS</button>
    </div>

    <div id="test-results"></div>

    <h2>快速訪問</h2>

    <div class="check">
        <a href="/swagger" target="_blank" class="test-btn">打開 Swagger UI</a>
        <a href="/swagger/spec" target="_blank" class="test-btn">查看 OpenAPI Spec</a>
    </div>

    <h2>瀏覽器信息</h2>
    <div class="check">
        <p>User Agent: <code id="userAgent"></code></p>
        <p>視窗大小: <code id="viewport"></code></p>
    </div>

    <h2>常見問題解決</h2>

    <div class="check">
        <h3>如果只看到一個連結：</h3>
        <ol>
            <li><strong>清除瀏覽器緩存</strong>：按 <kbd>Ctrl+Shift+R</kbd> (Windows) 或 <kbd>Cmd+Shift+R</kbd> (Mac)</li>
            <li><strong>檢查瀏覽器控制台</strong>：按 <kbd>F12</kbd> 打開開發者工具，查看 Console 標籤中的錯誤</li>
            <li><strong>檢查網絡請求</strong>：在開發者工具的 Network 標籤中，確認是否成功載入 CDN 資源</li>
            <li><strong>嘗試其他瀏覽器</strong>：使用 Chrome、Firefox 或 Edge 最新版本</li>
        </ol>
    </div>

    <div class="check">
        <h3>手動測試步驟：</h3>
        <div class="code">
# 1. 測試 Swagger UI 頁面
curl http://localhost:9230/swagger | head -20

# 2. 測試 OpenAPI 規範
curl http://localhost:9230/swagger/spec | head -10

# 3. 運行自動診斷
./scripts/test-swagger.sh
        </div>
    </div>

    <script>
        // Display browser info
        document.getElementById('userAgent').textContent = navigator.userAgent;
        document.getElementById('viewport').textContent = window.innerWidth + 'x' + window.innerHeight;

        function testEndpoint(url, endpointName) {
            const resultsDiv = document.getElementById('test-results');
            const resultDiv = document.createElement('div');
            resultDiv.className = 'result';
            resultDiv.innerHTML = 'Testing ' + endpointName + '... ';
            resultsDiv.appendChild(resultDiv);

            fetch(url)
                .then(response => {
                    if (response.ok) {
                        resultDiv.className = 'result success';
                        resultDiv.innerHTML = '✅ ' + endpointName + ': HTTP ' + response.status + ' ' + response.statusText;
                    } else {
                        resultDiv.className = 'result error';
                        resultDiv.innerHTML = '❌ ' + endpointName + ': HTTP ' + response.status + ' ' + response.statusText;
                    }
                })
                .catch(error => {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ ' + endpointName + ': ' + error.message;
                });
        }

        function testCDN(url, resourceName) {
            const resultsDiv = document.getElementById('test-results');
            const resultDiv = document.createElement('div');
            resultDiv.className = 'result';
            resultDiv.innerHTML = 'Testing CDN ' + resourceName + '... ';
            resultsDiv.appendChild(resultDiv);

            fetch(url, { mode: 'no-cors' })
                .then(() => {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '✅ CDN ' + resourceName + ': Accessible';
                })
                .catch(error => {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ CDN ' + resourceName + ': ' + error.message;
                });
        }

        // Auto-run basic tests
        setTimeout(() => {
            testEndpoint('/swagger', 'Swagger UI');
            testEndpoint('/swagger/spec', 'OpenAPI Spec');
        }, 500);
    </script>
</body>
</html>
HTML;
    }
}
