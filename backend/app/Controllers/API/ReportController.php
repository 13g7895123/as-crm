<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Report Controller
 *
 * Handles REST API endpoints for reports and analytics
 * Endpoints: GET /reports/dashboard, GET /reports/sales
 */
class ReportController extends BaseController
{
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
            // Mock statistics data
            $statistics = [
                'total_sales' => 4500000,
                'new_customers' => 12,
                'pending_orders' => 5,
                'active_users' => 25,
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

            // Mock sales data
            $salesData = [
                [
                    'period' => '2023-10',
                    'amount' => 1500000,
                ],
                [
                    'period' => '2023-11',
                    'amount' => 2000000,
                ],
                [
                    'period' => '2023-12',
                    'amount' => 1000000,
                ],
            ];

            return $this->respond([
                'data' => $salesData,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'group_by' => $groupBy,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得銷售報表失敗: ' . $e->getMessage());
        }
    }
}
