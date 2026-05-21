<?php

namespace Modules\Plant\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Plant\Services\PlantService;
use Modules\Plant\Http\Requests\StorePlantRequest;
use Modules\Plant\Http\Requests\UpdatePlantRequest;
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

    public function store(StorePlantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->plantService->storePlant(array_merge($data, [
            'created_by' => $request->user()->name ?? 'System'
        ]));

        return response()->json($result, $result['status'] === 1 ? 201 : 400);
    }

    public function update(UpdatePlantRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
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
