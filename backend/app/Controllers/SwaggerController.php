<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SwaggerController extends Controller
{
    /**
     * Serve Swagger UI HTML page
     */
    public function index()
    {
        // Load OpenAPI spec content
        $spec = $this->getOpenApiSpec();

        if (!$spec) {
            return $this->response
                ->setStatusCode(500)
                ->setBody('<h1>Error</h1><p>Failed to load OpenAPI specification</p>');
        }

        // Convert YAML to JSON for inline embedding
        $specJson = json_encode($this->yamlToArray($spec));

        $html = $this->getSwaggerUIHtml($specJson);

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($html);
    }

    /**
     * Get OpenAPI spec content
     */
    private function getOpenApiSpec()
    {
        $possiblePaths = [
            ROOTPATH . 'docs/openapi.yaml',
            APPPATH . '../docs/openapi.yaml',
            dirname(APPPATH) . '/docs/openapi.yaml',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return file_get_contents($path);
            }
        }

        return null;
    }

    /**
     * Simple YAML to Array converter (for basic OpenAPI structure)
     */
    private function yamlToArray($yaml)
    {
        // Use Symfony YAML if available, otherwise use basic parsing
        if (function_exists('yaml_parse')) {
            return yaml_parse($yaml);
        }

        // For now, we'll use the external spec URL instead
        return ['_external' => true];
    }

    /**
     * Generate Swagger UI HTML
     */
    private function getSwaggerUIHtml($specJson)
    {
        $spec = json_decode($specJson, true);
        $useExternalUrl = isset($spec['_external']) && $spec['_external'];

        // Generate the spec configuration
        if ($useExternalUrl) {
            $specConfig = 'url: "/swagger/spec"';
        } else {
            $specConfig = 'spec: ' . $specJson;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM RBAC API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .topbar {
            background-color: #1b1b1b;
            padding: 10px 20px;
        }

        .topbar-wrapper {
            display: flex;
            align-items: center;
        }

        .topbar-wrapper .link {
            color: #fff;
            font-size: 1.2em;
            font-weight: bold;
            text-decoration: none;
        }

        #swagger-ui {
            max-width: 1460px;
            margin: 0 auto;
        }

        .loading {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-wrapper">
            <a href="/" class="link">
                <span>CRM RBAC Permission Management API</span>
            </a>
        </div>
    </div>

    <div id="swagger-ui">
        <div class="loading">Loading API Documentation...</div>
    </div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.onload = function() {
            try {
                const ui = SwaggerUIBundle({
                    {$specConfig},
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    plugins: [
                        SwaggerUIBundle.plugins.DownloadUrl
                    ],
                    layout: "StandaloneLayout",
                    persistAuthorization: true,
                    tryItOutEnabled: true,
                    filter: true,
                    syntaxHighlight: {
                        activate: true,
                        theme: "monokai"
                    },
                    defaultModelsExpandDepth: 1,
                    defaultModelExpandDepth: 3,
                    displayRequestDuration: true,
                    docExpansion: "list",
                    operationsSorter: "alpha",
                    tagsSorter: "alpha",
                    onComplete: function() {
                        console.log("Swagger UI loaded successfully");
                    },
                    onFailure: function(error) {
                        console.error("Swagger UI failed to load:", error);
                        document.getElementById('swagger-ui').innerHTML =
                            '<div style="padding: 50px; color: red;">' +
                            '<h2>Failed to load API documentation</h2>' +
                            '<p>Error: ' + error + '</p>' +
                            '<p>Please check the console for more details.</p>' +
                            '</div>';
                    }
                });

                window.ui = ui;
            } catch (error) {
                console.error("Error initializing Swagger UI:", error);
                document.getElementById('swagger-ui').innerHTML =
                    '<div style="padding: 50px; color: red;">' +
                    '<h2>Error initializing Swagger UI</h2>' +
                    '<p>' + error.message + '</p>' +
                    '<p>Check browser console (F12) for details.</p>' +
                    '</div>';
            }
        };

        // Add error event listener for script loading
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof SwaggerUIBundle === 'undefined') {
                document.getElementById('swagger-ui').innerHTML =
                    '<div style="padding: 50px; color: red;">' +
                    '<h2>Failed to load Swagger UI libraries</h2>' +
                    '<p>CDN resources may be blocked. Please check your network connection.</p>' +
                    '</div>';
            }
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Serve OpenAPI specification YAML file
     */
    public function openapi()
    {
        // Try multiple possible paths
        $possiblePaths = [
            ROOTPATH . 'docs/openapi.yaml',
            APPPATH . '../docs/openapi.yaml',
            dirname(APPPATH) . '/docs/openapi.yaml',
        ];

        $filePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $filePath = $path;
                break;
            }
        }

        if (!$filePath) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'OpenAPI specification file not found',
                'searched_paths' => $possiblePaths
            ]);
        }

        return $this->response
            ->setHeader('Content-Type', 'text/yaml; charset=UTF-8')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Serve OpenAPI specification as JSON
     */
    public function openapiJson()
    {
        $filePath = ROOTPATH . 'docs/openapi.yaml';

        if (!file_exists($filePath)) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'OpenAPI specification file not found'
            ]);
        }

        // For simplicity, we're serving YAML for now
        // If you need JSON, you can use symfony/yaml to convert
        $yaml = file_get_contents($filePath);

        // Try to parse YAML and convert to JSON if yaml extension is available
        if (function_exists('yaml_parse')) {
            $data = yaml_parse($yaml);
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setJSON($data);
        }

        // Otherwise, return a message to use YAML
        return $this->response->setStatusCode(501)->setJSON([
            'error' => 'JSON format not available. Please use the YAML endpoint.',
            'yaml_url' => site_url('swagger/openapi.yaml')
        ]);
    }
}
