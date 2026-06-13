<?php declare(strict_types=1);

namespace Modules\TsWip\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsWip\Services\Contracts\WipEntryServiceInterface;
use Modules\TsWip\Http\Requests\StoreWipEntryRequest;
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
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $data = $this->wipEntryService->index($plantId);

            return ApiResponse::success($data, 'OK', 200);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /transactions/wip-entries
     * Handles all write operations via flag parameter
     */
    public function store(StoreWipEntryRequest $request): JsonResponse
    {
        try {
            /**
             * @todo Move this inline permission check to a middleware
             * (e.g. middleware('can:task-update') in routes)
             */
            if (!Auth::user()?->can('task-update')) {
                return ApiResponse::error('Forbidden', 403);
            }

            $flag = $request->input('flag');
            $user = Auth::user()->name ?? 'System';
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);

            return match ($flag) {
                'post_matlDocNumber' => $this->handleMaterialDocument($request, $user),
                'post_materialFeed'  => $this->handleMaterialFeed($request, $user, $plantId),
                'post_cancelFeed'    => $this->handleCancelFeed($request, $user),
                'post_cancelRundown' => $this->handleCancelRundown($request, $user),
                'post_materialRundown' => $this->handleMaterialRundown($request, $user, $plantId),
                'post_updateEntrySubTank' => $this->handleUpdateSubTank($request, $user),
                default => ApiResponse::error('Unknown flag', 400),
            };
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /transactions/wip-entries/{action}
     * Handles all read operations via flag query param
     */
    public function show(Request $request, $action): JsonResponse
    {
        try {
            // GAP #7: Add permission check for GET/show
            if (!Auth::user()?->can('task-read')) {
                return ApiResponse::error('Forbidden', 403);
            }

            $flag = $request->input('flag', $action);
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);

            return match ($flag) {
                'get_dtBalance'         => $this->handleBalance($request, $plantId),
                'get_dtFeed'            => $this->handleFeedData($request, $plantId),
                'get_dtRundown'         => $this->handleRundownData($request, $plantId),
                'get_feedNewBatchNumber' => $this->handleFeedNewBatch($request, $plantId),
                'get_rundownNewBatchNumber' => $this->handleRundownNewBatch($request, $plantId),
                'get_feedLastBatch'     => $this->handleFeedLastBatch($request, $plantId),
                'get_rundownLastBatch'  => $this->handleRundownLastBatch($request, $plantId),
                'get_cmbActiveTank_trf' => $this->handleActiveTanksFeed($request, $plantId),
                'get_cmbActiveTank_rundown' => $this->handleActiveTanksRundown($request, $plantId),
                'get_cmbActiveSpecificTank_trf' => $this->handleActiveSpecificTanks($request),
                'get_quantifierData'    => $this->handleQuantifierData($request),
                'get_wipTree'          => $this->handleWipTree($request),
                'get_newFeedNumber'    => $this->handleFeedNewNumber($request, $plantId),
                'get_newRundownNumber' => $this->handleRundownNewNumber($request, $plantId),
                default => ApiResponse::error('Unknown flag', 400),
            };
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HANDLERS
    // ─────────────────────────────────────────────────────────────────────────

    protected function handleMaterialDocument(Request $request, string $user): JsonResponse
    {
        $result = $this->wipEntryService->postMaterialDocument(
            $request->input('mode'),
            (int) $request->input('id'),
            $request->input('number'),
            $user
        );
        return ApiResponse::success($result, 'OK', 200);
    }

    protected function handleMaterialFeed(Request $request, string $user, $plantId): JsonResponse
    {
        $data = $request->all();
        $data['id_plant'] = $plantId;

        $result = $this->wipEntryService->postMaterialFeed($data, $user);
        return ApiResponse::success($result, 'OK', 200);
    }

    protected function handleCancelFeed(Request $request, string $user): JsonResponse
    {
        $result = $this->wipEntryService->cancelFeed($request->input('traceNo'), $user);
        return ApiResponse::success($result, 'OK', 200);
    }

    protected function handleCancelRundown(Request $request, string $user): JsonResponse
    {
        $result = $this->wipEntryService->cancelRundown($request->input('traceNo'), $user);
        return ApiResponse::success($result, 'OK', 200);
    }

    protected function handleMaterialRundown(Request $request, string $user, $plantId): JsonResponse
    {
        $data = $request->all();
        $data['id_plant'] = $plantId;

        $result = $this->wipEntryService->postMaterialRundown($data, $user);
        return ApiResponse::success($result, 'OK', 200);
    }

    protected function handleUpdateSubTank(Request $request, string $user): JsonResponse
    {
        $result = $this->wipEntryService->updateEntrySubTank(
            (int) $request->input('idHead'),
            $request->input('idTankTail', []),
            $user
        );
        return ApiResponse::success($result, 'OK', 200);
    }

    protected function handleBalance(Request $request, $plantId): JsonResponse
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 5));

        $result = $this->wipEntryService->getBalance(
            (string) $request->input('rundownId'),
            $plantId,
            $request->input('subgroup'),
            $page,
            $perPage
        );

        return ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['per_page']);
    }

    protected function handleFeedData(Request $request, $plantId): JsonResponse
    {
        $mode    = $request->input('mode', 'LATEST');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 5));

        $result = $this->wipEntryService->getFeed(
            $request->input('feedId'),
            $mode,
            $plantId,
            $page,
            $perPage
        );

        // LATEST mode: return simple array for backward-compat
        if ($mode !== 'LOG') {
            return ApiResponse::success($result['data'] ?? $result, 'OK', 200);
        }

        return ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['per_page']);
    }

    protected function handleRundownData(Request $request, $plantId): JsonResponse
    {
        $mode    = $request->input('mode', 'LATEST');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 5));

        $result = $this->wipEntryService->getRundown(
            $request->input('rundownId'),
            $mode,
            $plantId,
            $page,
            $perPage
        );

        // LATEST mode: return simple array for backward-compat
        if ($mode !== 'LOG') {
            return ApiResponse::success($result['data'] ?? $result, 'OK', 200);
        }

        return ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['per_page']);
    }

    protected function handleFeedNewBatch(Request $request, $plantId): JsonResponse
    {
        $data = $this->wipEntryService->getFeedNewBatchNumber(
            $request->input('feedID'),
            $plantId
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleRundownNewBatch(Request $request, $plantId): JsonResponse
    {
        $data = $this->wipEntryService->getRundownNewBatchNumber(
            $request->input('rundownID'),
            $plantId
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleFeedLastBatch(Request $request, $plantId): JsonResponse
    {
        $data = $this->wipEntryService->getFeedLastBatch(
            $request->input('feedID'),
            $plantId
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleRundownLastBatch(Request $request, $plantId): JsonResponse
    {
        $data = $this->wipEntryService->getRundownLastBatch(
            $request->input('rundownID'),
            $plantId
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleActiveTanksFeed(Request $request, $plantId): JsonResponse
    {
        $data = $this->wipEntryService->getActiveTanksForFeed(
            $request->input('feedID'),
            $plantId
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleActiveTanksRundown(Request $request, $plantId): JsonResponse
    {
        $data = $this->wipEntryService->getActiveTanksForRundown(
            $request->input('rundownID'),
            $plantId,
            $request->input('subgroup')
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleActiveSpecificTanks(Request $request): JsonResponse
    {
        $data = $this->wipEntryService->getActiveSpecificTanks(
            (int) $request->input('sloc')
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleQuantifierData(Request $request): JsonResponse
    {
        $data = $this->wipEntryService->getQuantifierData(
            $request->input('date'),
            $request->input('tagNumber')
        );
        return ApiResponse::success($data, 'OK', 200);
    }

    // B8: WIP Tree/Dashboard endpoint
    protected function handleWipTree(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $data = $this->wipEntryService->getWipTree($plantId);
        return ApiResponse::success($data, 'OK', 200);
    }

    // Auto Number Generation endpoints
    protected function handleFeedNewNumber(Request $request, $plantId): JsonResponse
    {
        $feedId = $request->input('feedId');
        $data = $this->wipEntryService->generateNewFeedNumber($feedId, $plantId);
        return ApiResponse::success($data, 'OK', 200);
    }

    protected function handleRundownNewNumber(Request $request, $plantId): JsonResponse
    {
        $rundownId = $request->input('rundownId');
        $subgroup = $request->input('subgroup');
        $data = $this->wipEntryService->generateNewRundownNumber($rundownId, $plantId, $subgroup);
        return ApiResponse::success($data, 'OK', 200);
    }

    /**
     * DELETE /transactions/wip-entries/{id}
     * Handles deactivation/deletion - BUG #1 fix
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            /**
             * @todo Move this inline permission check to a middleware
             * (e.g. middleware('can:task-update') in routes)
             */
            if (!Auth::user()?->can('task-update')) {
                return ApiResponse::error('Forbidden', 403);
            }

            $user = Auth::user()->name ?? 'System';

            $result = $this->wipEntryService->cancelById((int) $id, $user);
            return ApiResponse::success($result, 'OK', 200);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
