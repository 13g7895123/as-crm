<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\AuditLogModel;

/**
 * Archive Audit Logs Command
 *
 * Archives audit log entries older than 90 days to audit_logs_archive table.
 * Designed to run as a scheduled task (e.g., daily at midnight).
 *
 * Usage:
 *   php spark audit:archive
 *   php spark audit:archive --days=60  (custom retention period)
 */
class ArchiveAuditLogsCommand extends BaseCommand
{
    /**
     * Command group
     *
     * @var string
     */
    protected $group = 'Maintenance';

    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'audit:archive';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = '將 90 天前的審計記錄歸檔至 audit_logs_archive 表';

    /**
     * Command usage
     *
     * @var string
     */
    protected $usage = 'audit:archive [options]';

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
        '--days' => '自訂保留天數 (預設: 90)',
        '--dry-run' => '模擬執行,不實際歸檔',
    ];

    /**
     * Run the command
     *
     * @param array $params
     * @return void
     */
    public function run(array $params)
    {
        $days = $params['days'] ?? CLI::getOption('days') ?? 90;
        $dryRun = CLI::getOption('dry-run') !== null;

        CLI::write("開始審計記錄歸檔作業...", 'green');
        CLI::write("保留期限: {$days} 天");
        CLI::write("模擬執行: " . ($dryRun ? '是' : '否'));
        CLI::newLine();

        $db = \Config\Database::connect();
        $auditLogModel = new AuditLogModel();

        // Calculate cutoff date
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        CLI::write("歸檔日期界限: {$cutoffDate}");

        try {
            // Count records to archive
            $count = $db->table('audit_logs')
                ->where('created_at <', $cutoffDate)
                ->countAllResults();

            if ($count === 0) {
                CLI::write('沒有需要歸檔的記錄。', 'yellow');
                return;
            }

            CLI::write("找到 {$count} 筆需要歸檔的記錄。", 'yellow');

            if ($dryRun) {
                CLI::write('模擬執行完成,未實際歸檔。', 'cyan');
                return;
            }

            // Start transaction
            $db->transStart();

            // Create archive table if not exists
            $this->ensureArchiveTableExists($db);

            // Archive in batches to avoid memory issues
            $batchSize = 1000;
            $totalArchived = 0;

            CLI::write('開始分批歸檔...');
            CLI::showProgress(false);

            while (true) {
                // Get batch of records
                $records = $db->table('audit_logs')
                    ->where('created_at <', $cutoffDate)
                    ->limit($batchSize)
                    ->get()
                    ->getResultArray();

                if (empty($records)) {
                    break;
                }

                // Insert into archive table
                $db->table('audit_logs_archive')->insertBatch($records);

                // Delete from main table
                $ids = array_column($records, 'id');
                $db->table('audit_logs')->whereIn('id', $ids)->delete();

                $totalArchived += count($records);

                CLI::showProgress($totalArchived, $count);
            }

            CLI::showProgress(false);

            $db->transComplete();

            if ($db->transStatus() === false) {
                CLI::error('歸檔失敗: 資料庫交易錯誤');
                return;
            }

            CLI::newLine();
            CLI::write("成功歸檔 {$totalArchived} 筆記錄!", 'green');

            // Log the archive operation
            $this->logArchiveOperation($totalArchived, $cutoffDate);

        } catch (\Exception $e) {
            CLI::error('歸檔失敗: ' . $e->getMessage());
            log_message('error', 'Audit log archive failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure archive table exists
     *
     * @param \CodeIgniter\Database\BaseConnection $db
     * @return void
     */
    protected function ensureArchiveTableExists($db): void
    {
        $forge = \Config\Database::forge();

        if (!$db->tableExists('audit_logs_archive')) {
            CLI::write('建立歸檔表 audit_logs_archive...');

            $fields = [
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                ],
                'action' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                ],
                'target_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'target_id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'null' => true,
                ],
                'old_values' => [
                    'type' => 'JSON',
                    'null' => true,
                ],
                'new_values' => [
                    'type' => 'JSON',
                    'null' => true,
                ],
                'result' => [
                    'type' => 'ENUM',
                    'constraint' => ['success', 'failed', 'denied'],
                    'default' => 'success',
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                ],
                'user_agent' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'request_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 36,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                ],
                'archived_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ];

            $forge->addField($fields);
            $forge->addKey('id', true);
            $forge->addKey('user_id');
            $forge->addKey(['target_type', 'target_id']);
            $forge->addKey('created_at');
            $forge->createTable('audit_logs_archive', true);

            CLI::write('歸檔表建立完成!', 'green');
        }
    }

    /**
     * Log the archive operation itself
     *
     * @param int $count Number of archived records
     * @param string $cutoffDate Cutoff date
     * @return void
     */
    protected function logArchiveOperation(int $count, string $cutoffDate): void
    {
        try {
            $db = \Config\Database::connect();
            $db->table('audit_logs')->insert([
                'user_id' => 0, // System operation
                'action' => 'archive_audit_logs',
                'target_type' => 'audit_logs',
                'target_id' => null,
                'new_values' => json_encode([
                    'archived_count' => $count,
                    'cutoff_date' => $cutoffDate,
                ]),
                'result' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'CLI Command',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            CLI::write('無法記錄歸檔操作: ' . $e->getMessage(), 'yellow');
        }
    }
}
