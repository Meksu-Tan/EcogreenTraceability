<?php
declare(strict_types=1);
namespace Modules\TsWip\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsWip\Services\Contracts\WipEntryServiceInterface;
use Modules\TsWip\Http\Resources\WipEntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WipEntryController extends Controller
{
    protected $wipEntryService;

    public function __construct(WipEntryServiceInterface $wipEntryService)
    {
        $this->wipEntryService = $wipEntryService;
    }

    /**
     * GET /transactions/wip-entries
     * Returns initial page data (plants, sections config)
     */
    public function index(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        $data = $this->wipEntryService->index($plantId);

        return ApiResponse::success($data, 'OK', 200);
    }

    // POST Methods

    public function createMatlDoc(Request $request): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        return ApiResponse::success($this->wipEntryService->postMaterialDocument($request->input('mode'), (int) $request->input('id'), $request->input('number'), $user), 'OK', 200);
    }

    public function storeFeed(Request $request): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->postMaterialFeed(array_merge($request->all(), ['id_plant' => $plantId]), $user), 'OK', 200);
    }

    public function cancelFeed(Request $request): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        return ApiResponse::success($this->wipEntryService->cancelFeed($request->input('traceNo'), $user), 'OK', 200);
    }

    public function cancelRundown(Request $request): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        return ApiResponse::success($this->wipEntryService->cancelRundown($request->input('traceNo'), $user), 'OK', 200);
    }

    public function storeRundown(Request $request): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->postMaterialRundown(array_merge($request->all(), ['id_plant' => $plantId]), $user), 'OK', 200);
    }

    public function updateSubTank(Request $request): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        return ApiResponse::success($this->wipEntryService->updateEntrySubTank((int) $request->input('idHead'), $request->input('idSlocTail', []), $user), 'OK', 200);
    }

    // GET Methods

    public function getBalance(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 5));
        $res = $this->wipEntryService->getBalance((string)$request->input('rundownId'), $plantId, $request->input('subgroup'), $page, $perPage);
        return ApiResponse::paginated(WipEntryResource::collection($res['data'])->toArray($request), $res['total'], $res['page'], $res['per_page']);
    }

    public function getFeed(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 5)));
        $mode = $request->input('mode', 'LATEST');
        $res = $this->wipEntryService->getFeed($request->input('feedId'), $mode, $plantId, $page, $perPage);
        if ($mode !== 'LOG') return ApiResponse::success($res['data'] ?? $res, 'OK', 200);
        return ApiResponse::paginated(WipEntryResource::collection($res['data'])->toArray($request), $res['total'], $res['page'], $res['per_page']);
    }

    public function getRundown(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 5)));
        $mode = $request->input('mode', 'LATEST');
        $res = $this->wipEntryService->getRundown($request->input('rundownId'), $mode, $plantId, $page, $perPage);
        if ($mode !== 'LOG') return ApiResponse::success($res['data'] ?? $res, 'OK', 200);
        return ApiResponse::paginated(WipEntryResource::collection($res['data'])->toArray($request), $res['total'], $res['page'], $res['per_page']);
    }

    public function getFeedNewBatchNumber(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getFeedNewBatchNumber($request->input('feedID'), $plantId), 'OK', 200);
    }

    public function getRundownNewBatchNumber(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getRundownNewBatchNumber($request->input('rundownID'), $plantId), 'OK', 200);
    }

    public function getFeedLastBatch(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getFeedLastBatch($request->input('feedID'), $plantId), 'OK', 200);
    }

    public function getRundownLastBatch(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getRundownLastBatch($request->input('rundownID'), $plantId), 'OK', 200);
    }

    public function getActiveTanksForFeed(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getActiveTanksForFeed($request->input('feedID'), $plantId), 'OK', 200);
    }

    public function getActiveTanksForRundown(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getActiveTanksForRundown($request->input('rundownID'), $plantId, $request->input('subgroup')), 'OK', 200);
    }

    public function getActiveSpecificTanks(Request $request): JsonResponse
    {
        return ApiResponse::success($this->wipEntryService->getActiveSpecificTanks((int)$request->input('sloc')), 'OK', 200);
    }

    public function getQuantifierData(Request $request): JsonResponse
    {
        try {
            return ApiResponse::success($this->wipEntryService->getQuantifierData($request->input('date'), $request->input('tagNumber')), 'OK', 200);
        } catch (\Exception $e) {
            return ApiResponse::error("Gagal terhubung ke database Quantifier (dwsql). Silakan periksa koneksi atau coba lagi nanti. Pesan: " . $e->getMessage(), 200);
        }
    }

    public function getWipTree(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->getWipTree($plantId), 'OK', 200);
    }

    public function getNewFeedNumber(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->generateNewFeedNumber($request->input('feedId'), $plantId), 'OK', 200);
    }

    public function getNewRundownNumber(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
        return ApiResponse::success($this->wipEntryService->generateNewRundownNumber($request->input('rundownId'), $plantId, $request->input('subgroup')), 'OK', 200);
    }

    /**
     * DELETE /transactions/wip-entries/{id}
     * Handles deactivation/deletion
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = Auth::user()->name ?? 'System';
        $result = $this->wipEntryService->cancelById((int) $id, $user);
        return ApiResponse::success($result, 'OK', 200);
    }
}
