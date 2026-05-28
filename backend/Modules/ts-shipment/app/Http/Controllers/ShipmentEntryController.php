<?php declare(strict_types=1);

namespace Modules\TsShipment\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Shipment Entry module ready (implementations pending)', 200);
    }

    public function store(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Shipment Entry stored successfully', 200);
    }

    public function destroy($id): JsonResponse
    {
        return ApiResponse::success(null, 'Shipment Entry deactivated', 200);
    }
}

