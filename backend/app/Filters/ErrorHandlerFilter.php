<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Error Handler Filter
 *
 * Global error handling middleware for consistent error responses
 * Catches exceptions and formats them appropriately for API responses
 *
 * @package App\Filters
 */
class ErrorHandlerFilter implements FilterInterface
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

        // Check if response is an error (4xx or 5xx)
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            // Get response body
            $body = $response->getJSON();

            // If not already formatted as API error, format it
            if (!isset($body->status) || $body->status !== 'error') {
                $errorResponse = $this->formatErrorResponse($statusCode, $body);
                $response->setJSON($errorResponse);
            }

            // Log error if 5xx
            if ($statusCode >= 500) {
                $this->logError($request, $response, $statusCode);
            }
        }

        return $response;
    }

    /**
     * Format error response
     *
     * @param int $statusCode
     * @param mixed $body
     * @return array
     */
    private function formatErrorResponse(int $statusCode, $body): array
    {
        $message = $this->getDefaultMessage($statusCode);

        // If body contains a message, use it
        if (is_object($body) && isset($body->message)) {
            $message = $body->message;
        } elseif (is_array($body) && isset($body['message'])) {
            $message = $body['message'];
        } elseif (is_string($body)) {
            $message = $body;
        }

        $response = [
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
        ];

        // Include validation errors if present
        if (is_object($body) && isset($body->errors)) {
            $response['errors'] = $body->errors;
        } elseif (is_array($body) && isset($body['errors'])) {
            $response['errors'] = $body['errors'];
        }

        // Include trace in development mode
        if (ENVIRONMENT === 'development') {
            if (is_object($body) && isset($body->trace)) {
                $response['trace'] = $body->trace;
            } elseif (is_array($body) && isset($body['trace'])) {
                $response['trace'] = $body['trace'];
            }
        }

        return $response;
    }

    /**
     * Get default error message for status code
     *
     * @param int $statusCode
     * @return string
     */
    private function getDefaultMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Resource Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Validation Failed',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $messages[$statusCode] ?? 'An error occurred';
    }

    /**
     * Log error details
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param int $statusCode
     * @return void
     */
    private function logError(RequestInterface $request, ResponseInterface $response, int $statusCode): void
    {
        $logger = Services::logger();

        $logData = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status_code' => $statusCode,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->toString(),
            'response_body' => $response->getJSON(),
        ];

        $logger->error('API Error: ' . $statusCode, $logData);
    }
}
