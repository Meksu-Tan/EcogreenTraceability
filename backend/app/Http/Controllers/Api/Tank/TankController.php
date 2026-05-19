<?php

namespace App\Http\Controllers\Api\Tank;

use App\Http\Controllers\Controller;
use App\Services\Tank\TankService;
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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'          => 'nullable|integer',
            'plant_code'  => 'required|string|max:10',
            'plant_name'  => 'required|string|max:100',
            'tank_number' => 'required|string|max:50',
            'tank_height' => 'required|numeric',
        ]);

        $result = $this->tankService->storeTank(array_merge($data, [
            'created_by' => $request->user()->name ?? 'System'
        ]));

        return response()->json($result, $result['status'] === 1 ? 201 : 400);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'plant_code'  => 'required|string|max:10',
            'plant_name'  => 'required|string|max:100',
            'tank_number' => 'required|string|max:50',
            'tank_height' => 'required|numeric',
        ]);

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
