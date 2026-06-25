<?php
declare(strict_types=1);
namespace Modules\Tank\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tank\Services\TankService;
use Modules\Tank\Http\Requests\StoreTankRequest;
use Modules\Tank\Http\Requests\UpdateTankRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class TankController extends Controller
{
    public function __construct(
        protected TankService $tankService
    ) {}

    public function index(): JsonResponse
    {
        $tanks = $this->tankService->listTanks();
        return ApiResponse::success($tanks, 'OK', 200);
    }

    public function store(StoreTankRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->tankService->storeTank(array_merge($data, [
            'created_by' => $request->user()->name ?? 'System'
        ]));

        if ($result['status'] === 1) {
            return ApiResponse::success($result, 'Tank created', 201);
        }
        return ApiResponse::error($result['message'] ?? 'Failed to create tank', 400);
    }

    public function update(UpdateTankRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $result = $this->tankService->updateTank($id, array_merge($data, [
            'updated_by' => $request->user()->name ?? 'System'
        ]));

        if ($result['status'] === 1) {
            return ApiResponse::success($result, 'Tank updated');
        }
        return ApiResponse::error($result['message'] ?? 'Failed to update tank', 400);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name ?? 'System';

        $result = ($action === 'activate')
            ? $this->tankService->activateTank($id, $user)
            : $this->tankService->deactivateTank($id, $user);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, $action === 'activate' ? 'Tank activated' : 'Tank deactivated');
        }
        return ApiResponse::error($result['message'] ?? 'Failed to process tank', 400);
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user()->name ?? 'System';
        $result = $this->tankService->syncFromExternal($user);

        if (in_array($result['status'], [1, 2])) {
            return ApiResponse::success($result, $result['message'] ?? 'Tank sync completed');
        }
        return ApiResponse::error($result['message'] ?? 'Failed to sync tanks', 400);
    }

    public function lastSync(): JsonResponse
    {
        $info = $this->tankService->getLastSyncInfo();
        return ApiResponse::success($info, 'OK');
    }
}
