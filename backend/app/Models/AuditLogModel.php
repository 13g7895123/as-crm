<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Audit Log Model
 *
 * Records all significant actions performed by users for audit trail and compliance.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $resource_type
 * @property int|null $resource_id
 * @property string|null $details
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $created_at
 */
class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'details',
        'ip_address',
        'user_agent',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // No updated field for audit logs

    // Validation
    protected $validationRules = [
        'action' => 'required|max_length[100]',
        'resource_type' => 'required|max_length[100]',
    ];

    protected $validationMessages = [
        'action' => [
            'required' => '操作類型為必填',
            'max_length' => '操作類型長度不能超過 100 字元',
        ],
        'resource_type' => [
            'required' => '資源類型為必填',
            'max_length' => '資源類型長度不能超過 100 字元',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['decodeDetailsForJSON'];
    protected $afterFind      = ['encodeDetails'];

    /**
     * Decode details if it's an array
     */
    protected function decodeDetailsForJSON(array $data): array
    {
        if (isset($data['data']['details']) && is_array($data['data']['details'])) {
            $data['data']['details'] = json_encode($data['data']['details'], JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }

    /**
     * Encode details from JSON to array
     */
    protected function encodeDetails(array $data): array
    {
        if ($data['singleton'] ?? false) {
            if (isset($data['data']['details']) && is_string($data['data']['details'])) {
                $decoded = json_decode($data['data']['details'], true);
                $data['data']['details'] = $decoded !== null ? $decoded : null;
            }
        } else {
            foreach ($data['data'] as &$row) {
                if (isset($row['details']) && is_string($row['details'])) {
                    $decoded = json_decode($row['details'], true);
                    $row['details'] = $decoded !== null ? $decoded : null;
                }
            }
        }

        return $data;
    }

    /**
     * Get audit logs with filters
     *
     * @param array $filters
     * @return array
     */
    public function getLogsWithFilters(array $filters = []): array
    {
        $builder = $this->select('audit_logs.*, users.username, users.full_name')
            ->join('users', 'users.id = audit_logs.user_id', 'left');

        // Filter by user
        if (!empty($filters['user_id'])) {
            $builder->where('audit_logs.user_id', $filters['user_id']);
        }

        // Filter by action
        if (!empty($filters['action'])) {
            $builder->where('audit_logs.action', $filters['action']);
        }

        // Filter by resource type
        if (!empty($filters['resource_type'])) {
            $builder->where('audit_logs.resource_type', $filters['resource_type']);
        }

        // Filter by resource ID
        if (!empty($filters['resource_id'])) {
            $builder->where('audit_logs.resource_id', $filters['resource_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $builder->where('audit_logs.created_at >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('audit_logs.created_at <=', $filters['end_date']);
        }

        // Search in details
        if (!empty($filters['search'])) {
            $builder->like('audit_logs.details', $filters['search']);
        }

        // Pagination
        if (!empty($filters['per_page'])) {
            $page = $filters['page'] ?? 1;
            $perPage = (int)$filters['per_page'];
            $offset = ($page - 1) * $perPage;
            $builder->limit($perPage, $offset);
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'audit_logs.created_at';
        $order = $filters['order'] ?? 'DESC';
        $builder->orderBy($sortBy, $order);

        return $builder->findAll();
    }

    /**
     * Get total count with filters
     *
     * @param array $filters
     * @return int
     */
    public function countWithFilters(array $filters = []): int
    {
        $builder = $this->builder();

        // Filter by user
        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        // Filter by action
        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }

        // Filter by resource type
        if (!empty($filters['resource_type'])) {
            $builder->where('resource_type', $filters['resource_type']);
        }

        // Filter by resource ID
        if (!empty($filters['resource_id'])) {
            $builder->where('resource_id', $filters['resource_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $builder->where('created_at >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('created_at <=', $filters['end_date']);
        }

        // Search in details
        if (!empty($filters['search'])) {
            $builder->like('details', $filters['search']);
        }

        return $builder->countAllResults();
    }

    /**
     * Get logs by user
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getLogsByUser(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get logs by resource
     *
     * @param string $resourceType
     * @param int $resourceId
     * @return array
     */
    public function getLogsByResource(string $resourceType, int $resourceId): array
    {
        return $this->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get recent logs
     *
     * @param int $limit
     * @return array
     */
    public function getRecentLogs(int $limit = 100): array
    {
        return $this->select('audit_logs.*, users.username, users.full_name')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get action statistics
     *
     * @param array $filters
     * @return array
     */
    public function getActionStatistics(array $filters = []): array
    {
        $builder = $this->builder();

        // Apply filters
        if (!empty($filters['start_date'])) {
            $builder->where('created_at >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('created_at <=', $filters['end_date']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        return $builder->select('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get user activity statistics
     *
     * @param array $filters
     * @return array
     */
    public function getUserActivityStatistics(array $filters = []): array
    {
        $builder = $this->builder();

        // Apply filters
        if (!empty($filters['start_date'])) {
            $builder->where('audit_logs.created_at >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('audit_logs.created_at <=', $filters['end_date']);
        }

        return $builder->select('audit_logs.user_id, users.username, users.full_name, COUNT(*) as action_count')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->groupBy('audit_logs.user_id, users.username, users.full_name')
            ->orderBy('action_count', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Delete old logs
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted logs
     */
    public function deleteOldLogs(int $days = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->where('created_at <', $cutoffDate)->delete();
    }
}
