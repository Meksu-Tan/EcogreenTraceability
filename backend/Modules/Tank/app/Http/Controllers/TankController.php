<?php

namespace Modules\Tank\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tank\Services\TankService;
use Modules\Tank\Http\Requests\StoreTankRequest;
use Modules\Tank\Http\Requests\UpdateTankRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TankController extends Controller
{
    public function __construct(
        protected TankService $tankService
    ) {}

    public function index(): JsonResponse
    {
        $tanks = $this->tankService->listTanks();
        return response()->json(['status' => 1, 'data' => $tanks]);
    }

    public function store(StoreTankRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->tankService->storeTank(array_merge($data, [
            'created_by' => $request->user()->name ?? 'System'
        ]));

        return response()->json($result, $result['status'] === 1 ? 201 : 400);
    }

    public function update(UpdateTankRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $result = $this->tankService->updateTank($id, array_merge($data, [
            'updated_by' => $request->user()->name ?? 'System'
        ]));

        return response()->json($result, $result['status'] === 1 ? 200 : 400);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name ?? 'System';

        $result = ($action === 'activate')
            ? $this->tankService->activateTank($id, $user)
            : $this->tankService->deactivateTank($id, $user);

        return response()->json($result, $result['status'] === 1 ? 200 : 400);
    }
}
