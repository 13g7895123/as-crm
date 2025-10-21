<?php

namespace App\Helpers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Exception Handler
 *
 * Centralized exception handling for consistent API error responses
 * Provides methods to handle common exception types
 *
 * @package App\Helpers
 */
class ExceptionHandler
{
    /**
     * Handle exception and return formatted response
     *
     * @param \Throwable $exception
     * @return ResponseInterface
     */
    public static function handle(\Throwable $exception): ResponseInterface
    {
        $response = Services::response();
        $logger = Services::logger();

        // Determine status code and message based on exception type
        [$statusCode, $message, $errors] = self::parseException($exception);

        // Build error response
        $errorResponse = [
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
        ];

        // Include errors if present (validation errors, etc.)
        if (!empty($errors)) {
            $errorResponse['errors'] = $errors;
        }

        // Include exception details in development
        if (ENVIRONMENT === 'development') {
            $errorResponse['exception'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // Log error
        self::logException($exception, $statusCode);

        return $response->setJSON($errorResponse)->setStatusCode($statusCode);
    }

    /**
     * Parse exception and determine status code and message
     *
     * @param \Throwable $exception
     * @return array [statusCode, message, errors]
     */
    private static function parseException(\Throwable $exception): array
    {
        $statusCode = 500;
        $message = 'An unexpected error occurred';
        $errors = [];

        // Map specific exception types
        $exceptionType = get_class($exception);

        switch (true) {
            // Validation Exception
            case $exception instanceof \CodeIgniter\Validation\Exceptions\ValidationException:
            case str_contains($exceptionType, 'ValidationException'):
                $statusCode = 422;
                $message = 'Validation failed';
                // Extract validation errors if available
                if (method_exists($exception, 'getErrors')) {
                    $errors = $exception->getErrors();
                }
                break;

            // Not Found Exception
            case $exception instanceof \CodeIgniter\Exceptions\PageNotFoundException:
            case str_contains($exceptionType, 'NotFoundException'):
                $statusCode = 404;
                $message = $exception->getMessage() ?: 'Resource not found';
                break;

            // Unauthorized Exception
            case str_contains($exceptionType, 'UnauthorizedException'):
            case str_contains($exceptionType, 'AuthenticationException'):
                $statusCode = 401;
                $message = $exception->getMessage() ?: 'Unauthorized';
                break;

            // Forbidden Exception
            case str_contains($exceptionType, 'ForbiddenException'):
            case str_contains($exceptionType, 'AuthorizationException'):
                $statusCode = 403;
                $message = $exception->getMessage() ?: 'Forbidden';
                break;

            // Bad Request Exception
            case str_contains($exceptionType, 'BadRequestException'):
            case $exception instanceof \InvalidArgumentException:
                $statusCode = 400;
                $message = $exception->getMessage() ?: 'Bad request';
                break;

            // Conflict Exception
            case str_contains($exceptionType, 'ConflictException'):
                $statusCode = 409;
                $message = $exception->getMessage() ?: 'Conflict';
                break;

            // Database Exception
            case $exception instanceof \CodeIgniter\Database\Exceptions\DatabaseException:
                $statusCode = 500;
                $message = ENVIRONMENT === 'development'
                    ? $exception->getMessage()
                    : 'Database error occurred';
                break;

            // Runtime Exception
            case $exception instanceof \RuntimeException:
                $statusCode = 500;
                $message = $exception->getMessage() ?: 'Runtime error';
                break;

            // Default to 500
            default:
                $statusCode = 500;
                $message = ENVIRONMENT === 'development'
                    ? $exception->getMessage()
                    : 'An unexpected error occurred';
        }

        return [$statusCode, $message, $errors];
    }

    /**
     * Log exception
     *
     * @param \Throwable $exception
     * @param int $statusCode
     * @return void
     */
    private static function logException(\Throwable $exception, int $statusCode): void
    {
        $logger = Services::logger();

        $logLevel = $statusCode >= 500 ? 'critical' : 'error';

        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'status_code' => $statusCode,
        ];

        // Add request context if available
        $request = Services::request();
        if ($request) {
            $context['method'] = $request->getMethod();
            $context['uri'] = (string) $request->getUri();
            $context['ip_address'] = $request->getIPAddress();
        }

        $logger->log($logLevel, "Exception: {$exception->getMessage()}", $context);
    }

    /**
     * Create a validation error response
     *
     * @param array $errors Validation errors
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): ResponseInterface
    {
        $response = Services::response();

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 422,
            'errors' => $errors,
        ])->setStatusCode(422);
    }

    /**
     * Create a not found error response
     *
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        $response = Services::response();

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 404,
        ])->setStatusCode(404);
    }

    /**
     * Create an unauthorized error response
     *
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function unauthorized(string $message = 'Unauthorized'): ResponseInterface
    {
        $response = Services::response();

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 401,
        ])->setStatusCode(401);
    }

    /**
     * Create a forbidden error response
     *
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function forbidden(string $message = 'Forbidden'): ResponseInterface
    {
        $response = Services::response();

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 403,
        ])->setStatusCode(403);
    }

    /**
     * Create a bad request error response
     *
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function badRequest(string $message = 'Bad request'): ResponseInterface
    {
        $response = Services::response();

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 400,
        ])->setStatusCode(400);
    }

    /**
     * Create a conflict error response
     *
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function conflict(string $message = 'Conflict'): ResponseInterface
    {
        $response = Services::response();

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 409,
        ])->setStatusCode(409);
    }

    /**
     * Create an internal server error response
     *
     * @param string $message Optional custom message
     * @return ResponseInterface
     */
    public static function internalError(string $message = 'Internal server error'): ResponseInterface
    {
        $response = Services::response();
        $logger = Services::logger();

        // Log the error
        $logger->error($message);

        return $response->setJSON([
            'status' => 'error',
            'message' => $message,
            'code' => 500,
        ])->setStatusCode(500);
    }
}
