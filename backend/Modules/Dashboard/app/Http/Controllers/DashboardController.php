<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Dashboard\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $service
    ) {}

    /**
     * GET /api/v1/dashboard/stats
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getStats();

        return ApiResponse::success($stats, 'OK', 200);
    }
}
