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
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-wrapper">
            <a href="<?= $baseUrl ?>" class="link">
                <span>CRM RBAC Permission Management API</span>
            </a>
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Build a system
            const ui = SwaggerUIBundle({
                url: "<?= $openapiUrl ?>",
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
                tagsSorter: "alpha"
            });

            window.ui = ui;
        };
    </script>
</body>
</html>
