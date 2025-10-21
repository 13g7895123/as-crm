<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Rate Limit Filter
 *
 * Implements rate limiting to prevent API abuse
 * Uses IP-based throttling with configurable limits
 *
 * Configuration (in .env):
 * - RATE_LIMIT_ENABLED: true/false
 * - RATE_LIMIT_REQUESTS: Number of requests allowed
 * - RATE_LIMIT_PERIOD: Time period in seconds
 *
 * Response Headers:
 * - X-RateLimit-Limit: Maximum requests allowed
 * - X-RateLimit-Remaining: Remaining requests
 * - X-RateLimit-Reset: Unix timestamp when limit resets
 * - Retry-After: Seconds to wait before retrying (when limited)
 *
 * @package App\Filters
 */
class RateLimitFilter implements FilterInterface
{
    /**
     * Rate limit configuration
     */
    private int $maxRequests;
    private int $period;
    private bool $enabled;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Load configuration from environment
        $this->enabled = filter_var(
            getenv('RATE_LIMIT_ENABLED') ?: 'true',
            FILTER_VALIDATE_BOOLEAN
        );

        $this->maxRequests = (int) (getenv('RATE_LIMIT_REQUESTS') ?: 100);
        $this->period = (int) (getenv('RATE_LIMIT_PERIOD') ?: 60);
    }

    /**
     * Before the controller is called
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Skip if rate limiting is disabled
        if (!$this->enabled) {
            return $request;
        }

        // Get client identifier (IP address)
        $clientId = $this->getClientIdentifier($request);

        // Check rate limit
        $rateLimitData = $this->checkRateLimit($clientId);

        // Add rate limit headers to request for use in after()
        $request->rateLimitData = $rateLimitData;

        // If rate limit exceeded, return 429 response
        if ($rateLimitData['remaining'] < 0) {
            return $this->createRateLimitResponse($rateLimitData);
        }

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
        // Skip if rate limiting is disabled
        if (!$this->enabled) {
            return $response;
        }

        // Add rate limit headers to response
        if (isset($request->rateLimitData)) {
            $this->addRateLimitHeaders($response, $request->rateLimitData);
        }

        return $response;
    }

    /**
     * Get client identifier (IP address)
     *
     * @param RequestInterface $request
     * @return string
     */
    private function getClientIdentifier(RequestInterface $request): string
    {
        // Get IP address
        $ipAddress = $request->getIPAddress();

        // You could also use authenticated user ID for authenticated endpoints
        // Example: return 'user_' . auth()->id();

        return 'ip_' . $ipAddress;
    }

    /**
     * Check rate limit for client
     *
     * @param string $clientId
     * @return array Rate limit data
     */
    private function checkRateLimit(string $clientId): array
    {
        $cache = Services::cache();
        $cacheKey = 'rate_limit_' . $clientId;

        // Get current rate limit data from cache
        $data = $cache->get($cacheKey);

        $currentTime = time();

        if (!$data) {
            // First request - initialize
            $data = [
                'requests' => 1,
                'reset_time' => $currentTime + $this->period,
            ];

            $cache->save($cacheKey, $data, $this->period);
        } else {
            // Check if period has expired
            if ($currentTime >= $data['reset_time']) {
                // Reset counter
                $data = [
                    'requests' => 1,
                    'reset_time' => $currentTime + $this->period,
                ];

                $cache->save($cacheKey, $data, $this->period);
            } else {
                // Increment counter
                $data['requests']++;
                $ttl = $data['reset_time'] - $currentTime;
                $cache->save($cacheKey, $data, $ttl);
            }
        }

        // Calculate remaining requests
        $remaining = $this->maxRequests - $data['requests'];

        return [
            'limit' => $this->maxRequests,
            'remaining' => $remaining,
            'reset' => $data['reset_time'],
            'retry_after' => $data['reset_time'] - $currentTime,
        ];
    }

    /**
     * Create rate limit exceeded response
     *
     * @param array $rateLimitData
     * @return ResponseInterface
     */
    private function createRateLimitResponse(array $rateLimitData): ResponseInterface
    {
        $response = Services::response();

        $response->setJSON([
            'status' => 'error',
            'message' => 'Too many requests. Please try again later.',
            'code' => 429,
        ]);

        $response->setStatusCode(429);

        // Add rate limit headers
        $this->addRateLimitHeaders($response, $rateLimitData);

        // Add Retry-After header
        $response->setHeader('Retry-After', (string) $rateLimitData['retry_after']);

        return $response;
    }

    /**
     * Add rate limit headers to response
     *
     * @param ResponseInterface $response
     * @param array $rateLimitData
     * @return void
     */
    private function addRateLimitHeaders(ResponseInterface $response, array $rateLimitData): void
    {
        $response->setHeader('X-RateLimit-Limit', (string) $rateLimitData['limit']);
        $response->setHeader('X-RateLimit-Remaining', (string) max(0, $rateLimitData['remaining']));
        $response->setHeader('X-RateLimit-Reset', (string) $rateLimitData['reset']);
    }
}
