<?php

namespace App\Services;

use App\Models\AuditLogModel;

/**
 * Audit Log Service
 *
 * Provides business logic for audit logging and querying.
 */
class AuditLogService
{
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * Log an action
     *
     * @param array $data
     * @return int Log ID
     */
    public function log(array $data): int
    {
        // Add IP address and user agent if not provided
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }

        // Encode details if it's an array
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details'], JSON_UNESCAPED_UNICODE);
        }

        return $this->auditLogModel->insert($data);
    }

    /**
     * Log a user action
     *
     * @param int $userId
     * @param string $action
     * @param string $resourceType
     * @param int|null $resourceId
     * @param array|string|null $details
     * @return int
     */
    public function logUserAction(
        int $userId,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        $details = null
    ): int {
        return $this->log([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'details' => $details,
        ]);
    }

    /**
     * Log a system action
     *
     * @param string $action
     * @param string $resourceType
     * @param int|null $resourceId
     * @param array|string|null $details
     * @return int
     */
    public function logSystemAction(
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        $details = null
    ): int {
        return $this->log([
            'user_id' => null, // System action
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'details' => $details,
        ]);
    }

    /**
     * Get audit logs with filters and pagination
     *
     * @param array $filters
     * @return array
     */
    public function getLogs(array $filters = []): array
    {
        $logs = $this->auditLogModel->getLogsWithFilters($filters);
        $total = $this->auditLogModel->countWithFilters($filters);

        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return [
            'data' => $logs,
            'meta' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Get log by ID
     *
     * @param int $logId
     * @return array|null
     */
    public function getLogById(int $logId): ?array
    {
        $log = $this->auditLogModel->select('audit_logs.*, users.username, users.full_name, users.email')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->find($logId);

        return $log;
    }

    /**
     * Get user's audit logs
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserLogs(int $userId, int $limit = 50): array
    {
        return $this->auditLogModel->getLogsByUser($userId, $limit);
    }

    /**
     * Get resource audit trail
     *
     * @param string $resourceType
     * @param int $resourceId
     * @return array
     */
    public function getResourceAuditTrail(string $resourceType, int $resourceId): array
    {
        return $this->auditLogModel->getLogsByResource($resourceType, $resourceId);
    }

    /**
     * Get recent activity
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $limit = 100): array
    {
        return $this->auditLogModel->getRecentLogs($limit);
    }

    /**
     * Get action statistics
     *
     * @param array $filters
     * @return array
     */
    public function getActionStatistics(array $filters = []): array
    {
        return $this->auditLogModel->getActionStatistics($filters);
    }

    /**
     * Get user activity statistics
     *
     * @param array $filters
     * @return array
     */
    public function getUserActivityStatistics(array $filters = []): array
    {
        return $this->auditLogModel->getUserActivityStatistics($filters);
    }

    /**
     * Export audit logs to CSV
     *
     * @param array $filters
     * @return string CSV content
     */
    public function exportToCSV(array $filters = []): string
    {
        // Remove pagination for export
        unset($filters['page']);
        unset($filters['per_page']);

        $logs = $this->auditLogModel->getLogsWithFilters($filters);

        // CSV header
        $csv = "ID,使用者,操作,資源類型,資源ID,詳細資訊,IP位址,時間\n";

        // CSV rows
        foreach ($logs as $log) {
            $username = $log['username'] ?? '系統';
            $details = is_array($log['details'])
                ? json_encode($log['details'], JSON_UNESCAPED_UNICODE)
                : ($log['details'] ?? '');

            // Escape CSV fields
            $fields = [
                $log['id'],
                $this->escapeCsvField($username),
                $this->escapeCsvField($log['action']),
                $this->escapeCsvField($log['resource_type']),
                $log['resource_id'] ?? '',
                $this->escapeCsvField($details),
                $log['ip_address'] ?? '',
                $log['created_at'],
            ];

            $csv .= implode(',', $fields) . "\n";
        }

        return $csv;
    }

    /**
     * Export audit logs to JSON
     *
     * @param array $filters
     * @return string JSON content
     */
    public function exportToJSON(array $filters = []): string
    {
        // Remove pagination for export
        unset($filters['page']);
        unset($filters['per_page']);

        $logs = $this->auditLogModel->getLogsWithFilters($filters);

        return json_encode([
            'exported_at' => date('Y-m-d H:i:s'),
            'filters' => $filters,
            'total_records' => count($logs),
            'logs' => $logs,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Clean old audit logs
     *
     * @param int $days Number of days to keep (default: 90)
     * @return int Number of deleted logs
     */
    public function cleanOldLogs(int $days = 90): int
    {
        $deleted = $this->auditLogModel->deleteOldLogs($days);

        // Log the cleanup action
        $this->logSystemAction(
            'audit_logs_cleaned',
            'audit_log',
            null,
            [
                'days_threshold' => $days,
                'deleted_count' => $deleted,
            ]
        );

        return $deleted;
    }

    /**
     * Get audit log summary
     *
     * @param array $filters
     * @return array
     */
    public function getSummary(array $filters = []): array
    {
        $total = $this->auditLogModel->countWithFilters($filters);
        $actionStats = $this->getActionStatistics($filters);
        $userStats = $this->getUserActivityStatistics($filters);

        // Get most active users (top 5)
        $topUsers = array_slice($userStats, 0, 5);

        // Get most common actions (top 5)
        $topActions = array_slice($actionStats, 0, 5);

        return [
            'total_logs' => $total,
            'top_users' => $topUsers,
            'top_actions' => $topActions,
            'filters_applied' => $filters,
        ];
    }

    /**
     * Escape CSV field
     *
     * @param string $field
     * @return string
     */
    private function escapeCsvField(string $field): string
    {
        // Escape double quotes
        $field = str_replace('"', '""', $field);

        // Wrap in quotes if contains comma, newline, or quote
        if (strpos($field, ',') !== false || strpos($field, "\n") !== false || strpos($field, '"') !== false) {
            $field = '"' . $field . '"';
        }

        return $field;
    }
}
