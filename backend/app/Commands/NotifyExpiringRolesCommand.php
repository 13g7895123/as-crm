<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\RoleAssignmentService;
use App\Models\UserModel;

/**
 * Notify Expiring Roles Command
 *
 * Sends notifications for role assignments expiring soon.
 * Notifications are sent at 7, 3, and 1 day(s) before expiry.
 * Should be run daily via cron.
 *
 * Usage:
 *   php spark roles:notify-expiring
 */
class NotifyExpiringRolesCommand extends BaseCommand
{
    /**
     * Command group
     *
     * @var string
     */
    protected $group = 'Roles';

    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'roles:notify-expiring';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = '發送即將過期角色的通知';

    /**
     * Command usage
     *
     * @var string
     */
    protected $usage = 'roles:notify-expiring [options]';

    /**
     * Command arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Command options
     *
     * @var array
     */
    protected $options = [
        '--days' => '指定檢查天數 (預設: 7,3,1)',
        '--dry-run' => '僅顯示將被通知的記錄，不實際發送通知',
        '--verbose' => '顯示詳細資訊',
    ];

    /**
     * Notification thresholds in days
     *
     * @var array
     */
    protected $thresholds = [7, 3, 1];

    /**
     * Run the command
     *
     * @param array $params
     * @return int
     */
    public function run(array $params)
    {
        $dryRun = array_key_exists('dry-run', $params) || CLI::getOption('dry-run');
        $verbose = array_key_exists('verbose', $params) || CLI::getOption('verbose');
        $customDays = CLI::getOption('days');

        // Parse custom days if provided
        if ($customDays) {
            $this->thresholds = array_map('intval', explode(',', $customDays));
        }

        CLI::write('開始檢查即將過期的角色指派...', 'yellow');
        CLI::newLine();

        try {
            $roleAssignmentService = new RoleAssignmentService();
            $userModel = new UserModel();
            $totalNotifications = 0;

            // Check each threshold
            foreach ($this->thresholds as $days) {
                CLI::write("檢查 {$days} 天內即將過期的角色...", 'cyan');

                $expiringRoles = $this->getExpiringRolesForThreshold($roleAssignmentService, $days);
                $count = count($expiringRoles);

                if ($count === 0) {
                    CLI::write("  沒有找到 {$days} 天內過期的角色。", 'green');
                    continue;
                }

                CLI::write("  找到 {$count} 筆即將過期的角色。", 'yellow');

                // Display details if verbose
                if ($verbose) {
                    $this->displayExpiringRoles($expiringRoles, $days);
                }

                // Send notifications if not dry run
                if ($dryRun) {
                    CLI::write("  [模擬模式] 將發送 {$count} 則通知。", 'cyan');
                } else {
                    $sent = $this->sendNotifications($expiringRoles, $days, $userModel);
                    CLI::write("  成功發送 {$sent} 則通知。", 'green');
                    $totalNotifications += $sent;
                }

                CLI::newLine();
            }

            if (!$dryRun && $totalNotifications > 0) {
                log_message('info', "Sent {$totalNotifications} expiring role notifications via command");
            }

            CLI::write('通知檢查完成！', 'green');
            CLI::write("總共處理 {$totalNotifications} 則通知。", 'green');

            return 0;
        } catch (\Exception $e) {
            CLI::error('通知發送失敗: ' . $e->getMessage());
            log_message('error', 'Failed to send expiring role notifications: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Get expiring roles for specific threshold
     *
     * @param RoleAssignmentService $service
     * @param int $days
     * @return array
     */
    protected function getExpiringRolesForThreshold(RoleAssignmentService $service, int $days): array
    {
        // Get roles expiring exactly within this threshold window
        $allExpiring = $service->getExpiringRoles($days);

        // Filter to get only roles expiring within the exact threshold
        // For example, if checking 7 days, get roles expiring between 6.5 and 7.5 days
        $filtered = [];
        $now = time();
        $minTime = $now + (($days - 0.5) * 86400); // 0.5 day tolerance
        $maxTime = $now + (($days + 0.5) * 86400);

        foreach ($allExpiring as $role) {
            $expiryTime = strtotime($role['expires_at']);
            if ($expiryTime >= $minTime && $expiryTime <= $maxTime) {
                $filtered[] = $role;
            }
        }

        return $filtered;
    }

    /**
     * Display expiring roles details
     *
     * @param array $expiringRoles
     * @param int $days
     * @return void
     */
    protected function displayExpiringRoles(array $expiringRoles, int $days): void
    {
        CLI::newLine();
        CLI::write("  角色列表 (即將於 {$days} 天後過期):", 'yellow');

        $tableData = [];
        foreach ($expiringRoles as $role) {
            $tableData[] = [
                'ID' => $role['id'],
                '使用者' => $role['username'] ?? 'N/A',
                '信箱' => $role['email'] ?? 'N/A',
                '角色' => $role['display_name'] ?? $role['name'],
                '過期時間' => $role['expires_at'],
            ];
        }

        CLI::table($tableData, ['ID', '使用者', '信箱', '角色', '過期時間']);
    }

    /**
     * Send notifications for expiring roles
     *
     * @param array $expiringRoles
     * @param int $days
     * @param UserModel $userModel
     * @return int Number of notifications sent
     */
    protected function sendNotifications(array $expiringRoles, int $days, UserModel $userModel): int
    {
        $sent = 0;

        foreach ($expiringRoles as $role) {
            try {
                // Get user details
                $user = $userModel->find($role['user_id']);
                if (!$user || empty($user['email'])) {
                    CLI::write("  警告: 使用者 ID {$role['user_id']} 沒有信箱地址", 'yellow');
                    continue;
                }

                // Send notification
                $this->sendNotification($user, $role, $days);
                $sent++;
            } catch (\Exception $e) {
                CLI::write("  錯誤: 無法發送通知給使用者 ID {$role['user_id']}: {$e->getMessage()}", 'red');
                log_message('error', "Failed to send expiring role notification to user {$role['user_id']}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Send notification to user
     *
     * @param array $user
     * @param array $role
     * @param int $days
     * @return void
     */
    protected function sendNotification(array $user, array $role, int $days): void
    {
        // TODO: Implement actual notification sending (email, SMS, push notification, etc.)
        // For now, just log the notification

        $message = "您的角色「{$role['display_name']}」將於 {$days} 天後過期 (過期時間: {$role['expires_at']})。請聯絡管理員延長權限。";

        log_message('info', "Notification sent to {$user['email']}: {$message}");

        // Example email implementation (uncomment when email is configured):
        /*
        $email = \Config\Services::email();
        $email->setTo($user['email']);
        $email->setSubject('角色即將過期通知');
        $email->setMessage($this->getEmailTemplate($user, $role, $days));

        if (!$email->send()) {
            throw new \RuntimeException('Email sending failed: ' . $email->printDebugger());
        }
        */

        // Example in-app notification (implement based on your notification system):
        /*
        $notificationModel = new \App\Models\NotificationModel();
        $notificationModel->insert([
            'user_id' => $user['id'],
            'type' => 'role_expiring',
            'title' => '角色即將過期',
            'message' => $message,
            'data' => json_encode([
                'role_assignment_id' => $role['id'],
                'role_id' => $role['role_id'],
                'expires_at' => $role['expires_at'],
                'days_remaining' => $days,
            ], JSON_UNESCAPED_UNICODE),
            'is_read' => 0,
        ]);
        */
    }

    /**
     * Get email template for notification
     *
     * @param array $user
     * @param array $role
     * @param int $days
     * @return string
     */
    protected function getEmailTemplate(array $user, array $role, int $days): string
    {
        $expiryDate = date('Y年m月d日 H:i', strtotime($role['expires_at']));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>角色即將過期通知</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #ff9800;">角色即將過期通知</h2>

        <p>親愛的 {$user['full_name'] ?? $user['username']}，</p>

        <p>您的角色權限即將過期，詳細資訊如下：</p>

        <div style="background-color: #f5f5f5; padding: 15px; border-left: 4px solid #ff9800; margin: 20px 0;">
            <p><strong>角色名稱：</strong>{$role['display_name']}</p>
            <p><strong>過期時間：</strong>{$expiryDate}</p>
            <p><strong>剩餘天數：</strong>{$days} 天</p>
        </div>

        <p>如果您需要延長此角色的有效期限，請聯絡系統管理員。</p>

        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

        <p style="color: #999; font-size: 12px;">
            這是系統自動發送的通知郵件，請勿直接回覆。
        </p>
    </div>
</body>
</html>
HTML;
    }
}
