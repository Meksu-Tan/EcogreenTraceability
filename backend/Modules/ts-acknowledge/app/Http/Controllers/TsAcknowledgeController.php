<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\TsAcknowledge\Http\Requests\FetchDcsRequest;
use Modules\TsAcknowledge\Http\Requests\GetDashboardRequest;
use Modules\TsAcknowledge\Http\Requests\StoreAcknowledgeRequest;
use Modules\TsAcknowledge\Services\AcknowledgeService;

class TsAcknowledgeController extends Controller
{
    public function __construct(private AcknowledgeService $service) {}

    public function getDashboard(GetDashboardRequest $request): JsonResponse
    {
        $result = $this->service->getDashboardStructure(
            $request->input('plant_code'),
            $request->input('date'),
            $request->input('type', 'WIP'),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 15),
            $request->input('section_id') ? (int) $request->input('section_id') : null
        );

        $extra = [];
        if (isset($result['pagination'])) {
            $extra['pagination'] = $result['pagination'];
        }
        if (isset($result['allSections'])) {
            $extra['allSections'] = $result['allSections'];
        }

        return ApiResponse::success($result['data'] ?? $result, 'Acknowledge dashboard retrieved', 200, $extra);
    }

    public function fetchDcs(FetchDcsRequest $request): JsonResponse
    {
        return ApiResponse::success([], 'DCS fetched successfully');
    }

    public function store(StoreAcknowledgeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()?->username ?? $request->user()?->name;
        $data['updated_by'] = $request->user()?->username ?? $request->user()?->name;

        $record = $this->service->save($data);

        return ApiResponse::success($record->toArray(), 'Acknowledge record saved');
    }
}
