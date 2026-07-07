<?php

declare(strict_types=1);

namespace Modules\TsWip\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TsWip\Http\Requests\ReorderWipProcessStepRequest;
use Modules\TsWip\Http\Requests\ReorderWipSectionRequest;
use Modules\TsWip\Http\Requests\StoreWipProcessStepRequest;
use Modules\TsWip\Http\Requests\StoreWipSectionRequest;
use Modules\TsWip\Http\Requests\UpdateWipProcessStepRequest;
use Modules\TsWip\Http\Requests\UpdateWipSectionRequest;
use Modules\TsWip\Services\Contracts\WipProcessServiceInterface;

class WipProcessController extends Controller
{
    public function __construct(
        private WipProcessServiceInterface $service
    ) {}

    public function sections(Request $request): JsonResponse
    {
        $plantId = $request->query('id_plant');

        return ApiResponse::success($this->service->sections($plantId ? (string) $plantId : null), 'OK', 200);
    }

    public function storeSection(StoreWipSectionRequest $request): JsonResponse
    {
        return ApiResponse::success($this->service->createSection($request->validated()), 'Section created.', 201);
    }

    public function updateSection(UpdateWipSectionRequest $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->service->updateSection($id, $request->validated()), 'Section updated.', 200);
    }

    public function destroyAllSections(Request $request): JsonResponse
    {
        $plantId = $request->query('plant_id');

        return ApiResponse::success($this->service->deleteAllSections($plantId), 'All sections deleted.', 200);
    }

    public function destroyAllSteps(int $sectionId): JsonResponse
    {
        return ApiResponse::success($this->service->deleteAllSteps($sectionId), 'All steps deleted.', 200);
    }

    public function destroySection(int $id): JsonResponse
    {
        return ApiResponse::success($this->service->deleteSection($id), 'Section deactivated.', 200);
    }

    public function storeStep(StoreWipProcessStepRequest $request): JsonResponse
    {
        return ApiResponse::success($this->service->createStep($request->validated()), 'Step created.', 201);
    }

    public function updateStep(UpdateWipProcessStepRequest $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->service->updateStep($id, $request->validated()), 'Step updated.', 200);
    }

    public function destroyStep(int $id): JsonResponse
    {
        return ApiResponse::success($this->service->deleteStep($id), 'Step deactivated.', 200);
    }

    public function reorderSections(ReorderWipSectionRequest $request): JsonResponse
    {
        return ApiResponse::success($this->service->reorderSections($request->validated()['items']), 'Sections reordered.', 200);
    }

    public function reorderSteps(ReorderWipProcessStepRequest $request): JsonResponse
    {
        return ApiResponse::success($this->service->reorderSteps($request->validated()['items']), 'Steps reordered.', 200);
    }
}
