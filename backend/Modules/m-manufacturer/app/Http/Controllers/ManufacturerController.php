<?php declare(strict_types=1);
namespace Modules\Manufacturer\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturer\Services\ManufacturerService;
use Modules\Manufacturer\Http\Requests\StoreManufacturerRequest;
use Modules\Manufacturer\Http\Requests\UpdateManufacturerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class ManufacturerController extends Controller
{
    public function __construct(
        protected ManufacturerService $manufacturerService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->manufacturerService->listManufacturers(), 'OK', 200);
    }

    public function active(): JsonResponse
    {
        return ApiResponse::success($this->manufacturerService->getActiveManufacturers(), 'OK', 200);
    }

    public function store(StoreManufacturerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data   = array_merge($validated, ['created_by' => $request->user()->name ?? 'System']);
        $result = $this->manufacturerService->storeManufacturer($data);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, 'Manufacturer created', 201);
        }
        return ApiResponse::error($result['message'] ?? 'Failed to create manufacturer', 422);
    }

    public function update(UpdateManufacturerRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $data   = array_merge($validated, ['updated_by' => $request->user()->name ?? 'System']);
        $result = $this->manufacturerService->updateManufacturer($id, $data);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, 'Manufacturer updated');
        }
        return ApiResponse::error($result['message'] ?? 'Failed to update manufacturer', 422);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name ?? 'System';
        $result = $action === 'activate'
            ? $this->manufacturerService->activateManufacturer($id, $user)
            : $this->manufacturerService->deactivateManufacturer($id, $user);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, $action === 'activate' ? 'Manufacturer activated' : 'Manufacturer deactivated');
        }
        return ApiResponse::error($result['message'] ?? 'Failed to process manufacturer', 422);
    }
}
