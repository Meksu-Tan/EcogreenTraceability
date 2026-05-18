<?php

namespace App\Http\Controllers\Api\Manufacturer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturer\StoreManufacturerRequest;
use App\Http\Requests\Manufacturer\UpdateManufacturerRequest;
use App\Services\Manufacturer\ManufacturerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    public function __construct(
        protected ManufacturerService $manufacturerService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->manufacturerService->listManufacturers()]);
    }

    public function active(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->manufacturerService->getActiveManufacturers()]);
    }

    public function store(StoreManufacturerRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->manufacturerService->storeManufacturer($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    public function update(UpdateManufacturerRequest $request, int $id): JsonResponse
    {
        $data   = array_merge($request->validated(), ['updated_by' => $request->user()->name]);
        $result = $this->manufacturerService->updateManufacturer($id, $data);
        return response()->json($result);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->manufacturerService->activateManufacturer($id, $user)
            : $this->manufacturerService->deactivateManufacturer($id, $user);
        return response()->json($result);
    }
}
