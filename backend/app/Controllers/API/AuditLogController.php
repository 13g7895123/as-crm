<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuditLogService;

/**
 * Audit Log API Controller
 *
 * Provides endpoints for querying and exporting audit logs.
 */
class AuditLogController extends ResourceController
{
    protected $modelName = 'App\Models\AuditLogModel';
    protected $format    = 'json';
    protected AuditLogService $auditLogService;

    public function __construct()
    {
        $this->auditLogService = new AuditLogService();
    }

    /**
     * Get all audit logs with filters
     *
     * GET /api/v1/audit-logs
     *
     * Query parameters:
     * - user_id: Filter by user
     * - action: Filter by action type
     * - resource_type: Filter by resource type
     * - resource_id: Filter by resource ID
     * - start_date: Start date (YYYY-MM-DD HH:MM:SS)
     * - end_date: End date (YYYY-MM-DD HH:MM:SS)
     * - search: Search in details
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 20)
     * - sort: Sort field (default: created_at)
     * - order: Sort order (ASC/DESC, default: DESC)
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $filters = [
                'user_id' => $this->request->getGet('user_id'),
                'action' => $this->request->getGet('action'),
                'resource_type' => $this->request->getGet('resource_type'),
                'resource_id' => $this->request->getGet('resource_id'),
                'start_date' => $this->request->getGet('start_date'),
                'end_date' => $this->request->getGet('end_date'),
                'search' => $this->request->getGet('search'),
                'page' => $this->request->getGet('page') ?? 1,
                'per_page' => $this->request->getGet('per_page') ?? 20,
                'sort' => $this->request->getGet('sort') ?? 'audit_logs.created_at',
                'order' => $this->request->getGet('order') ?? 'DESC',
            ];

            $result = $this->auditLogService->getLogs($filters);

            return $this->respond([
                'data' => $result['data'],
                'meta' => $result['meta'],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get audit logs: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取審計記錄失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific audit log
     *
     * GET /api/v1/audit-logs/{id}
     *
     * @param int|string $id Log ID
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        try {
            $log = $this->auditLogService->getLogById((int)$id);

            if (!$log) {
                return $this->failNotFound([
                    'error' => '審計記錄不存在',
                ]);
            }

            return $this->respond(['data' => $log]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get audit log: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's audit logs
     *
     * GET /api/v1/audit-logs/user/{userId}
     *
     * Query parameters:
     * - limit: Number of logs to return (default: 50)
     *
     * @param int|string $userId User ID
     * @return ResponseInterface
     */
    public function userLogs($userId = null): ResponseInterface
    {
        try {
            $limit = (int)($this->request->getGet('limit') ?? 50);
            $logs = $this->auditLogService->getUserLogs((int)$userId, $limit);

            return $this->respond([
                'data' => $logs,
                'meta' => [
                    'user_id' => (int)$userId,
                    'count' => count($logs),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get user audit logs: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取使用者審計記錄失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get resource audit trail
     *
     * GET /api/v1/audit-logs/resource/{resourceType}/{resourceId}
     *
     * @param string $resourceType Resource type
     * @param int|string $resourceId Resource ID
     * @return ResponseInterface
     */
    public function resourceAuditTrail($resourceType = null, $resourceId = null): ResponseInterface
    {
        try {
            $logs = $this->auditLogService->getResourceAuditTrail($resourceType, (int)$resourceId);

            return $this->respond([
                'data' => $logs,
                'meta' => [
                    'resource_type' => $resourceType,
                    'resource_id' => (int)$resourceId,
                    'count' => count($logs),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get resource audit trail: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取資源審計記錄失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent activity
     *
     * GET /api/v1/audit-logs/recent
     *
     * Query parameters:
     * - limit: Number of logs to return (default: 100)
     *
     * @return ResponseInterface
     */
    public function recent(): ResponseInterface
    {
        try {
            $limit = (int)($this->request->getGet('limit') ?? 100);
            $logs = $this->auditLogService->getRecentActivity($limit);

            return $this->respond([
                'data' => $logs,
                'meta' => [
                    'count' => count($logs),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get recent activity: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取最近活動失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get audit log statistics
     *
     * GET /api/v1/audit-logs/statistics
     *
     * Query parameters:
     * - start_date: Start date
     * - end_date: End date
     * - user_id: Filter by user
     *
     * @return ResponseInterface
     */
    public function statistics(): ResponseInterface
    {
        try {
            $filters = [
                'start_date' => $this->request->getGet('start_date'),
                'end_date' => $this->request->getGet('end_date'),
                'user_id' => $this->request->getGet('user_id'),
            ];

            $actionStats = $this->auditLogService->getActionStatistics($filters);
            $userStats = $this->auditLogService->getUserActivityStatistics($filters);

            return $this->respond([
                'data' => [
                    'action_statistics' => $actionStats,
                    'user_activity' => $userStats,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get statistics: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取統計資料失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get audit log summary
     *
     * GET /api/v1/audit-logs/summary
     *
     * Query parameters:
     * - start_date: Start date
     * - end_date: End date
     * - user_id: Filter by user
     * - action: Filter by action
     * - resource_type: Filter by resource type
     *
     * @return ResponseInterface
     */
    public function summary(): ResponseInterface
    {
        try {
            $filters = [
                'start_date' => $this->request->getGet('start_date'),
                'end_date' => $this->request->getGet('end_date'),
                'user_id' => $this->request->getGet('user_id'),
                'action' => $this->request->getGet('action'),
                'resource_type' => $this->request->getGet('resource_type'),
            ];

            $summary = $this->auditLogService->getSummary($filters);

            return $this->respond(['data' => $summary]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get summary: ' . $e->getMessage());
            return $this->fail([
                'error' => '獲取摘要失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export audit logs
     *
     * POST /api/v1/audit-logs/export
     *
     * Request body:
     * {
     *   "format": "csv" | "json",
     *   "filters": {
     *     "user_id": 1,
     *     "start_date": "2025-01-01",
     *     "end_date": "2025-12-31"
     *   }
     * }
     *
     * @return ResponseInterface
     */
    public function export(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            $format = $data['format'] ?? 'csv';
            $filters = $data['filters'] ?? [];

            if ($format === 'csv') {
                $content = $this->auditLogService->exportToCSV($filters);
                $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
                $contentType = 'text/csv';
            } elseif ($format === 'json') {
                $content = $this->auditLogService->exportToJSON($filters);
                $filename = 'audit_logs_' . date('Y-m-d_His') . '.json';
                $contentType = 'application/json';
            } else {
                return $this->fail([
                    'error' => '不支援的格式',
                    'message' => '僅支援 csv 或 json 格式',
                ], 400);
            }

            // Log the export action
            $userId = $this->getCurrentUserId();
            $this->auditLogService->logUserAction(
                $userId,
                'audit_logs_exported',
                'audit_log',
                null,
                [
                    'format' => $format,
                    'filters' => $filters,
                ]
            );

            return $this->response
                ->setContentType($contentType)
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($content);
        } catch (\Exception $e) {
            log_message('error', 'Failed to export audit logs: ' . $e->getMessage());
            return $this->fail([
                'error' => '匯出失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user ID from JWT token
     * TODO: Implement proper JWT token validation
     *
     * @return int
     */
    protected function getCurrentUserId(): int
    {
        // Mock implementation - replace with actual JWT validation
        return 1;
    }
}
