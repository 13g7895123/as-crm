<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel
 *
 * 使用者資料模型
 * 管理 users 表的 CRUD 操作
 */
class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'username',
        'email',
        'password_hash',
        'full_name',
        'department',
        'region',
        'is_active',
        'last_login_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // 驗證規則
    protected $validationRules = [
        'username' => [
            'label' => '使用者名稱',
            'rules' => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        ],
        'email' => [
            'label' => '電子郵件',
            'rules' => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        ],
        'password_hash' => [
            'label' => '密碼',
            'rules' => 'permit_empty|min_length[8]',
        ],
        'is_active' => [
            'label' => '啟用狀態',
            'rules' => 'permit_empty|in_list[0,1]',
        ],
    ];

    protected $validationMessages = [
        'username' => [
            'required' => '使用者名稱為必填欄位',
            'min_length' => '使用者名稱至少需要 3 個字元',
            'max_length' => '使用者名稱不可超過 100 個字元',
            'is_unique' => '此使用者名稱已被使用',
        ],
        'email' => [
            'required' => '電子郵件為必填欄位',
            'valid_email' => '請輸入有效的電子郵件地址',
            'max_length' => '電子郵件不可超過 255 個字元',
            'is_unique' => '此電子郵件已被使用',
        ],
        'password_hash' => [
            'min_length' => '密碼至少需要 8 個字元',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * 在插入或更新前將密碼雜湊
     *
     * @param array $data
     *
     * @return array
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            unset($data['data']['password']);
        }

        return $data;
    }

    /**
     * 依使用者名稱查詢
     *
     * @param string $username
     *
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * 依電子郵件查詢
     *
     * @param string $email
     *
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * 檢查使用者是否啟用
     *
     * @param int $userId
     *
     * @return bool
     */
    public function isActive(int $userId): bool
    {
        $user = $this->find($userId);
        return $user && $user['is_active'] == 1;
    }
}
