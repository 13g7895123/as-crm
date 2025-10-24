<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Security Headers Filter
 *
 * Adds security-related HTTP headers to all responses
 * Helps protect against common web vulnerabilities
 *
 * Headers added:
 * - X-Content-Type-Options: Prevent MIME type sniffing
 * - X-Frame-Options: Prevent clickjacking
 * - X-XSS-Protection: Enable XSS filtering
 * - Strict-Transport-Security: Enforce HTTPS
 * - Content-Security-Policy: Control resource loading
 * - Referrer-Policy: Control referrer information
 * - Permissions-Policy: Control browser features
 *
 * @package App\Filters
 */
class SecurityHeadersFilter implements FilterInterface
{
    /**
     * Before the controller is called
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // No action needed before controller
        return $request;
    }

    /**
     * After the controller is called
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Skip for CLI requests (spark commands, migrations, etc.)
        if (is_cli()) {
            return $response;
        }

        // Get environment
        $environment = getenv('CI_ENVIRONMENT') ?: 'production';

        // Get request URI
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Check if this is a Swagger UI request
        $isSwaggerUI = strpos($path, '/swagger') === 0;

        // X-Content-Type-Options
        // Prevents browsers from MIME-sniffing a response away from the declared content-type
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options
        // Prevents the page from being embedded in a frame/iframe (clickjacking protection)
        $response->setHeader('X-Frame-Options', 'DENY');

        // X-XSS-Protection
        // Enables XSS filtering built into most browsers
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Strict-Transport-Security (HSTS)
        // Forces browsers to use HTTPS (only in production)
        if ($environment === 'production') {
            // max-age=31536000 (1 year), includeSubDomains
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content-Security-Policy (CSP)
        // Controls which resources the browser is allowed to load
        $csp = $this->getContentSecurityPolicy($environment, $isSwaggerUI);
        $response->setHeader('Content-Security-Policy', $csp);

        // Referrer-Policy
        // Controls how much referrer information should be included with requests
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy (formerly Feature-Policy)
        // Controls which browser features can be used
        $permissionsPolicy = $this->getPermissionsPolicy();
        $response->setHeader('Permissions-Policy', $permissionsPolicy);

        // Remove server signature
        $response->removeHeader('Server');
        $response->removeHeader('X-Powered-By');

        // Set custom server header (optional - can be removed for more security)
        // $response->setHeader('Server', 'WebServer');

        return $response;
    }

    /**
     * Get Content Security Policy string
     *
     * @param string $environment
     * @param bool $isSwaggerUI Whether this is a Swagger UI request
     * @return string
     */
    private function getContentSecurityPolicy(string $environment, bool $isSwaggerUI = false): string
    {
        // Special CSP for Swagger UI - needs to load external resources
        if ($isSwaggerUI) {
            return implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://unpkg.com",
                "style-src 'self' 'unsafe-inline' https://unpkg.com",
                "img-src 'self' data: https://unpkg.com",
                "font-src 'self' https://unpkg.com",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);
        }

        // Default CSP for API (very restrictive)
        $directives = [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // In development, allow unsafe-inline for easier debugging
        if ($environment === 'development') {
            // Note: These will be overridden by more specific directives above
            // but kept for documentation purposes
            $directives[1] = "script-src 'self' 'unsafe-inline' 'unsafe-eval'";
            $directives[2] = "style-src 'self' 'unsafe-inline'";
        }

        return implode('; ', $directives);
    }

    /**
     * Get Permissions Policy string
     *
     * @return string
     */
    private function getPermissionsPolicy(): string
    {
        // Disable most browser features by default
        // Note: 'ambient-light-sensor' is not recognized in all browsers
        $policies = [
            'geolocation=()',          // Disable geolocation
            'microphone=()',           // Disable microphone
            'camera=()',               // Disable camera
            'payment=()',              // Disable payment request API
            'usb=()',                  // Disable USB
            'magnetometer=()',         // Disable magnetometer
            'gyroscope=()',            // Disable gyroscope
            'accelerometer=()',        // Disable accelerometer
            // 'ambient-light-sensor=()', // Removed - not recognized in all browsers
            'autoplay=()',             // Disable autoplay
            'encrypted-media=()',      // Disable encrypted media
            'picture-in-picture=()',   // Disable picture-in-picture
        ];

        return implode(', ', $policies);
    }
}
