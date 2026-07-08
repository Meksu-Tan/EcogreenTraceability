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
        $plantCode = $request->input('plant_code');
        $date = $request->input('date');
        $type = $request->input('type', 'WIP');
        $scope = $request->input('scope', 'row');

        if ($scope === 'all') {
            $sectionId = $request->input('section_id') ? (int) $request->input('section_id') : null;
            $results = $this->service->fetchDcsForAll($plantCode, $date, $type, $sectionId);
            return ApiResponse::success($results, 'DCS fetched for all rows');
        }

        $sectionId = $request->input('section_id') ? (int) $request->input('section_id') : null;
        $stepType = $request->input('step_type');
        $stepId = $request->input('step_id') ? (int) $request->input('step_id') : null;
        $transactionId = $request->input('transaction_id');

        if ($type === 'WIP' && !$stepType) {
            return ApiResponse::error('step_type is required for WIP row fetch');
        }

        if ($type !== 'WIP' && !$transactionId) {
            return ApiResponse::error('transaction_id is required for non-WIP row fetch');
        }

        $result = $this->service->fetchDcsForStep($plantCode, $date, $type, $sectionId, $stepType ?? '', $transactionId !== null ? (int) $transactionId : $stepId);

        if ($result === null) {
            return ApiResponse::error('DCS tag not found or unsupported type');
        }

        return ApiResponse::success($result, 'DCS fetched successfully');
    }

    public function syncDcs(FetchDcsRequest $request): JsonResponse
    {
        $plantCode = $request->input('plant_code');
        $date = $request->input('date');
        $type = $request->input('type', 'WIP');
        $sectionId = $request->input('section_id') ? (int) $request->input('section_id') : null;
        $stepType = $request->input('step_type');
        $transactionId = $request->input('transaction_id');

        if ($type === 'WIP' && !$stepType) {
            return ApiResponse::error('step_type is required for WIP sync');
        }

        if ($type !== 'WIP' && !$transactionId) {
            return ApiResponse::error('transaction_id is required for non-WIP sync');
        }

        $success = $this->service->syncDcsToEoDls($plantCode, $date, $type, $sectionId, $stepType ?? '', $transactionId !== null ? (int) $transactionId : null);

        if (!$success) {
            return ApiResponse::error('Failed to sync DCS to EO/DLS');
        }

        return ApiResponse::success(null, 'DCS synced to EO/DLS successfully');
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
