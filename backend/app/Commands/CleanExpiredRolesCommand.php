<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\RoleAssignmentService;

/**
 * Clean Expired Roles Command
 *
 * Removes expired role assignments from the database.
 * Should be run periodically (e.g., every 5 minutes via cron).
 *
 * Usage:
 *   php spark roles:clean-expired
 */
class CleanExpiredRolesCommand extends BaseCommand
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
    protected $name = 'roles:clean-expired';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = '清理已過期的角色指派';

    /**
     * Command usage
     *
     * @var string
     */
    protected $usage = 'roles:clean-expired [options]';

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
        '--dry-run' => '僅顯示將被刪除的記錄，不實際執行刪除',
        '--verbose' => '顯示詳細資訊',
    ];

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

        CLI::write('開始清理過期的角色指派...', 'yellow');
        CLI::newLine();

        try {
            $roleAssignmentService = new RoleAssignmentService();

            // Get expired roles first
            $expiredRoles = $roleAssignmentService->getExpiredRoles();
            $count = count($expiredRoles);

            if ($count === 0) {
                CLI::write('沒有找到過期的角色指派。', 'green');
                return 0;
            }

            CLI::write("找到 {$count} 筆過期的角色指派。", 'yellow');
            CLI::newLine();

            // Display details if verbose
            if ($verbose) {
                $this->displayExpiredRoles($expiredRoles);
                CLI::newLine();
            }

            // Perform cleanup if not dry run
            if ($dryRun) {
                CLI::write('[模擬模式] 不會實際刪除記錄。', 'cyan');
                CLI::write("將刪除 {$count} 筆記錄。", 'cyan');
            } else {
                $deleted = $roleAssignmentService->cleanExpiredRoles();
                CLI::write("成功刪除 {$deleted} 筆過期的角色指派。", 'green');

                // Log the cleanup
                log_message('info', "Cleaned {$deleted} expired role assignments via command");
            }

            CLI::newLine();
            CLI::write('清理完成！', 'green');

            return 0;
        } catch (\Exception $e) {
            CLI::error('清理失敗: ' . $e->getMessage());
            log_message('error', 'Failed to clean expired roles: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Display expired roles details
     *
     * @param array $expiredRoles
     * @return void
     */
    protected function displayExpiredRoles(array $expiredRoles): void
    {
        CLI::write('過期的角色指派列表:', 'yellow');
        CLI::newLine();

        $tableData = [];
        foreach ($expiredRoles as $role) {
            $tableData[] = [
                'ID' => $role['id'],
                '使用者' => $role['username'] ?? 'N/A',
                '角色' => $role['display_name'] ?? $role['name'],
                '過期時間' => $role['expires_at'],
            ];
        }

        CLI::table($tableData, ['ID', '使用者', '角色', '過期時間']);
    }
}
