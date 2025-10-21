<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Condition Rule Model
 *
 * Manages conditional access rules for permissions
 * Supports department, region, customer_group, order_status, and amount_range conditions
 */
class ConditionRuleModel extends Model
{
    protected $table            = 'condition_rules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'permission_id',
        'condition_type',
        'field_name',
        'operator',
        'value',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'permission_id'  => 'permit_empty|integer',
        'condition_type' => 'required|in_list[department,region,customer_group,order_status,amount_range]',
        'field_name'     => 'required|max_length[100]',
        'operator'       => 'required|in_list[equals,not_equals,in,not_in,greater_than,less_than,between]',
        'value'          => 'permit_empty',
    ];

    protected $validationMessages = [
        'condition_type' => [
            'required' => '條件類型為必填',
            'in_list'  => '無效的條件類型',
        ],
        'field_name' => [
            'required' => '欄位名稱為必填',
        ],
        'operator' => [
            'required' => '運算子為必填',
            'in_list'  => '無效的運算子',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['validateConditionValue', 'encodeValue'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['validateConditionValue', 'encodeValue'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = ['decodeValue'];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Valid condition types
     */
    const CONDITION_TYPES = [
        'department',
        'region',
        'customer_group',
        'order_status',
        'amount_range',
    ];

    /**
     * Valid operators
     */
    const OPERATORS = [
        'equals',
        'not_equals',
        'in',
        'not_in',
        'greater_than',
        'less_than',
        'between',
    ];

    /**
     * Validate condition value based on type and operator
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function validateConditionValue(array $data): array
    {
        if (!isset($data['data']['condition_type']) || !isset($data['data']['operator'])) {
            return $data;
        }

        $conditionType = $data['data']['condition_type'];
        $operator = $data['data']['operator'];

        // Validate condition type
        if (!in_array($conditionType, self::CONDITION_TYPES)) {
            throw new \InvalidArgumentException('無效的條件類型: ' . $conditionType);
        }

        // Validate operator
        if (!in_array($operator, self::OPERATORS)) {
            throw new \InvalidArgumentException('無效的運算子: ' . $operator);
        }

        return $data;
    }

    /**
     * Encode value to JSON before insert/update
     *
     * @param array $data
     * @return array
     */
    protected function encodeValue(array $data): array
    {
        if (isset($data['data']['value']) && !is_string($data['data']['value'])) {
            $data['data']['value'] = json_encode($data['data']['value'], JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }

    /**
     * Decode JSON value after find
     *
     * @param array $data
     * @return array
     */
    protected function decodeValue(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$row) {
                    if (isset($row['value'])) {
                        $decoded = json_decode($row['value'], true);
                        $row['value'] = $decoded !== null ? $decoded : $row['value'];
                    }
                }
            }
        } elseif (isset($data['singleton']) && $data['singleton'] && isset($data['data']['value'])) {
            $decoded = json_decode($data['data']['value'], true);
            $data['data']['value'] = $decoded !== null ? $decoded : $data['data']['value'];
        }

        return $data;
    }

    /**
     * Get condition rules for a permission
     *
     * @param int $permissionId
     * @return array
     */
    public function getRulesByPermission(int $permissionId): array
    {
        return $this->where('permission_id', $permissionId)->findAll();
    }

    /**
     * Get global condition rules (permission_id IS NULL)
     *
     * @return array
     */
    public function getGlobalRules(): array
    {
        return $this->where('permission_id', null)->findAll();
    }

    /**
     * Assign condition rules to permission
     *
     * @param int|null $permissionId
     * @param array $rules
     * @return bool
     */
    public function assignRules(?int $permissionId, array $rules): bool
    {
        // Start transaction
        $this->db->transStart();

        // Remove existing rules for this permission
        if ($permissionId !== null) {
            $this->where('permission_id', $permissionId)->delete();
        }

        // Add new rules
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                $this->insert([
                    'permission_id'  => $permissionId,
                    'condition_type' => $rule['condition_type'],
                    'field_name'     => $rule['field_name'] ?? $rule['condition_type'],
                    'operator'       => $rule['operator'],
                    'value'          => $rule['condition_value'] ?? $rule['value'] ?? null,
                ]);
            }
        }

        // Complete transaction
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Check if a context matches condition rules
     *
     * @param array $rules
     * @param array $context
     * @return bool
     */
    public function matchesConditions(array $rules, array $context): bool
    {
        foreach ($rules as $rule) {
            $fieldName = $rule['field_name'];
            $operator = $rule['operator'];
            $conditionValue = is_string($rule['value'])
                ? json_decode($rule['value'], true)
                : $rule['value'];

            // Get context value
            $contextValue = $context[$fieldName] ?? null;

            if (!$this->evaluateCondition($contextValue, $operator, $conditionValue)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     *
     * @param mixed $contextValue
     * @param string $operator
     * @param mixed $conditionValue
     * @return bool
     */
    private function evaluateCondition($contextValue, string $operator, $conditionValue): bool
    {
        switch ($operator) {
            case 'equals':
                return $contextValue == ($conditionValue['value'] ?? $conditionValue);

            case 'not_equals':
                return $contextValue != ($conditionValue['value'] ?? $conditionValue);

            case 'in':
                $values = $conditionValue['values'] ?? $conditionValue;
                return is_array($values) && in_array($contextValue, $values);

            case 'not_in':
                $values = $conditionValue['values'] ?? $conditionValue;
                return is_array($values) && !in_array($contextValue, $values);

            case 'greater_than':
                return $contextValue > ($conditionValue['value'] ?? $conditionValue);

            case 'less_than':
                return $contextValue < ($conditionValue['value'] ?? $conditionValue);

            case 'between':
                $min = $conditionValue['min'] ?? $conditionValue[0] ?? null;
                $max = $conditionValue['max'] ?? $conditionValue[1] ?? null;
                return $contextValue >= $min && $contextValue <= $max;

            default:
                return false;
        }
    }

    /**
     * Get human-readable description of a condition rule
     *
     * @param array $rule
     * @return string
     */
    public function getDisplayText(array $rule): string
    {
        $field = $this->getFieldDisplayName($rule['field_name']);
        $operator = $this->getOperatorDisplayName($rule['operator']);

        $value = is_string($rule['value'])
            ? json_decode($rule['value'], true)
            : $rule['value'];

        $valueText = $this->formatValueForDisplay($value, $rule['operator']);

        return "{$field} {$operator} {$valueText}";
    }

    /**
     * Get field display name
     *
     * @param string $fieldName
     * @return string
     */
    private function getFieldDisplayName(string $fieldName): string
    {
        $names = [
            'department'      => '部門',
            'region'          => '區域',
            'customer_group'  => '客戶分群',
            'order_status'    => '訂單狀態',
            'amount_range'    => '金額',
        ];

        return $names[$fieldName] ?? $fieldName;
    }

    /**
     * Get operator display name
     *
     * @param string $operator
     * @return string
     */
    private function getOperatorDisplayName(string $operator): string
    {
        $names = [
            'equals'       => '等於',
            'not_equals'   => '不等於',
            'in'           => '包含於',
            'not_in'       => '不包含於',
            'greater_than' => '大於',
            'less_than'    => '小於',
            'between'      => '介於',
        ];

        return $names[$operator] ?? $operator;
    }

    /**
     * Format value for display
     *
     * @param mixed $value
     * @param string $operator
     * @return string
     */
    private function formatValueForDisplay($value, string $operator): string
    {
        if ($operator === 'in' || $operator === 'not_in') {
            $values = $value['values'] ?? $value;
            return is_array($values) ? implode(', ', $values) : (string) $values;
        }

        if ($operator === 'between') {
            $min = $value['min'] ?? $value[0] ?? '';
            $max = $value['max'] ?? $value[1] ?? '';
            return "{$min} ~ {$max}";
        }

        return (string) ($value['value'] ?? $value);
    }
}
