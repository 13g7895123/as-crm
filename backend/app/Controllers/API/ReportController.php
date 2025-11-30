<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\OrderService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Report Controller
 *
 * Handles REST API endpoints for reports and analytics
 * Endpoints: GET /reports/dashboard, GET /reports/sales
 */
class ReportController extends BaseController
{
    protected $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    /**
     * GET /api/v1/reports/dashboard
     *
     * Get dashboard statistics
     *
     * @return ResponseInterface
     */
    public function dashboard(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();

            // Total sales
            $totalSalesResult = $db->table('orders')
                ->selectSum('amount', 'total')
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            // New customers this month
            $newCustomers = $db->table('customers')
                ->where('deleted_at', null)
                ->where('DATE_FORMAT(created_at, "%Y-%m")', date('Y-m'))
                ->countAllResults();

            // Pending orders
            $pendingOrders = $db->table('orders')
                ->where('status', 'pending')
                ->where('deleted_at', null)
                ->countAllResults();

            // Active users
            $activeUsers = $db->table('auth_identities')
                ->where('deleted_at', null)
                ->distinct()
                ->countAllResults();

            $statistics = [
                'total_sales'      => (float)($totalSalesResult['total'] ?? 0),
                'new_customers'    => $newCustomers,
                'pending_orders'   => $pendingOrders,
                'active_users'     => $activeUsers,
            ];

            return $this->respond([
                'data' => $statistics,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得儀表板統計失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/reports/sales
     *
     * Get sales report over a period
     *
     * @return ResponseInterface
     */
    public function sales(): ResponseInterface
    {
        try {
            $startDate = $this->request->getGet('start_date');
            $endDate = $this->request->getGet('end_date');
            $groupBy = $this->request->getGet('group_by') ?? 'month';

            $salesData = $this->orderService->getSalesTrends($startDate, $endDate, $groupBy);

            return $this->respond([
                'data' => $salesData,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'group_by'   => $groupBy,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得銷售報表失敗: ' . $e->getMessage());
        }
    }
}
