<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\PermissionService;

/**
 * Permission Filter
 *
 * Checks if the authenticated user has the required permission(s) to access a route.
 *
 * Usage in Routes.php:
 * $routes->get('customers', 'CustomerController::index', ['filter' => 'permission:customer.view']);
 * $routes->post('customers', 'CustomerController::create', ['filter' => 'permission:customer.create']);
 */
class PermissionFilter implements FilterInterface
{
    /**
     * Before filter
     *
     * @param RequestInterface $request
     * @param array|null $arguments Permission name(s) required
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get current user ID from session/JWT
        $userId = $this->getCurrentUserId($request);

        if (!$userId) {
            return $this->errorResponse($request, 401, '未授權', '請先登入');
        }

        // Get required permissions from arguments
        $requiredPermissions = $arguments ?? [];
        if (empty($requiredPermissions)) {
            // No specific permission required, just authentication
            return $request;
        }

        // Check permissions
        $permissionService = new PermissionService();

        // Get resource context from request
        $context = $this->getResourceContext($request);

        try {
            foreach ($requiredPermissions as $permission) {
                $hasPermission = $permissionService->userHasPermission($userId, $permission, $context);

                if (!$hasPermission) {
                    return $this->errorResponse($request, 403, '權限不足', '您沒有權限執行此操作', ['required_permission' => $permission]);
                }
            }

            return $request;
        } catch (\Exception $e) {
            log_message('error', 'Permission check failed: ' . $e->getMessage());
            return $this->errorResponse($request, 500, '權限檢查失敗', $e->getMessage());
        }
    }

    /**
     * After filter
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after
    }

    /**
     * Get current user ID from request
     * TODO: Replace with actual JWT token validation
     *
     * @param RequestInterface $request
     * @return int|null
     */
    protected function getCurrentUserId(RequestInterface $request): ?int
    {
        // Try to get from session first
        $session = session();
        $userId = $session->get('user_id');

        if ($userId) {
            return (int)$userId;
        }

        // Try to get from JWT token
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader->getValue());
            // TODO: Decode JWT and get user ID
            // For now, return mock user ID
            return 1;
        }

        return null;
    }

    /**
     * Get resource context from request
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function getResourceContext(RequestInterface $request): array
    {
        $context = [];

        // Get request body data
        $data = $request->getJSON(true) ?? $request->getPost();

        // Extract common context fields
        if (is_array($data)) {
            if (isset($data['department'])) {
                $context['department'] = $data['department'];
            }
            if (isset($data['region'])) {
                $context['region'] = $data['region'];
            }
            if (isset($data['customer_group'])) {
                $context['customer_group'] = $data['customer_group'];
            }
            if (isset($data['order_status'])) {
                $context['order_status'] = $data['order_status'];
            }
            if (isset($data['amount'])) {
                $context['amount'] = $data['amount'];
            }
        }

        // Get query parameters
        $query = $request->getGet();
        if (is_array($query)) {
            if (isset($query['department'])) {
                $context['department'] = $query['department'];
            }
            if (isset($query['region'])) {
                $context['region'] = $query['region'];
            }
        }

        return $context;
    }

    /**
     * Create error response with CORS headers
     *
     * @param RequestInterface $request
     * @param int $statusCode
     * @param string $error
     * @param string $message
     * @param array $additionalData
     * @return ResponseInterface
     */
    protected function errorResponse(RequestInterface $request, int $statusCode, string $error, string $message, array $additionalData = []): ResponseInterface
    {
        $response = service('response');

        $data = array_merge([
            'error' => $error,
            'message' => $message,
        ], $additionalData);

        $response->setJSON($data)->setStatusCode($statusCode);

        // 添加 CORS headers，避免前端 CORS 錯誤
        // 因為 PermissionFilter 是 before filter，如果返回 response 會跳過 after filters (包括 CorsFilter)
        $corsConfig = config('Cors');
        $origin = $request->getHeaderLine('Origin');

        if ($origin && $corsConfig->isOriginAllowed($origin)) {
            $corsHeaders = $corsConfig->getHeaders($origin);
            foreach ($corsHeaders as $key => $value) {
                $response->setHeader($key, $value);
            }
        }

        return $response;
    }
}
