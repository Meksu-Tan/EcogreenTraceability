<?php

namespace App\Http\Controllers\Api\Plant;

use App\Http\Controllers\Controller;
use App\Services\Plant\PlantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function __construct(
        protected PlantService $plantService
    ) {}

    public function index(): JsonResponse
    {
        $plants = $this->plantService->listPlants();
        return response()->json(['status' => 1, 'data' => $plants]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'        => 'nullable|string',
            'code_2'      => 'required|string',
            'code_3'      => 'required|string',
            'description' => 'required|string',
        ]);

        $result = $this->plantService->storePlant(array_merge($data, [
            'created_by' => $request->user()->name ?? 'System'
        ]));

        return response()->json($result, $result['status'] === 1 ? 201 : 400);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code'        => 'nullable|string',
            'code_2'      => 'required|string',
            'code_3'      => 'required|string',
            'description' => 'required|string',
        ]);

        $result = $this->plantService->updatePlant($id, array_merge($data, [
            'updated_by' => $request->user()->name ?? 'System'
        ]));

        return response()->json($result, $result['status'] === 1 ? 200 : 400);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name ?? 'System';

        $result = ($action === 'activate')
            ? $this->plantService->activatePlant($id, $user)
            : $this->plantService->deactivatePlant($id, $user);

        return response()->json($result, $result['status'] === 1 ? 200 : 400);
    }
}
