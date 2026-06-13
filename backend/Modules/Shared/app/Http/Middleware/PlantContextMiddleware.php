<?php declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Modules\Shared\Services\PlantContextService;
use Symfony\Component\HttpFoundation\Response;

/**
 * PlantContextMiddleware - Middleware to auto-inject plant context
 *
 * Functions:
 * 1. Extract plant_id from request (header, query, body)
 * 2. Resolve to code_3 format
 * 3. Validate user access
 * 4. Set on request for downstream use
 *
 * Route usage:
 * Route::middleware(['auth:sanctum', 'plant.context'])->group(function () {
 *     // All routes in this group will have plant context
 * });
 */
class PlantContextMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Extract plant_id from various sources
        $plantId = $this->extractPlantId($request);

        // Resolve plant
        $resolvedCode = PlantContextService::resolvePlantId($plantId, $user->id);

        // Get user plants for dropdown/selector
        $userPlants = PlantContextService::getUserPlants($user->id);

        // Get default plant
        $defaultPlant = PlantContextService::getDefaultPlant($user->id);

        // Inject into request attributes (accessible via $request->get('plant_context'))
        $request->attributes->set('plant_context', [
            'plant_code' => $resolvedCode,
            'plant_id' => $plantId,
            'is_all_plants' => $resolvedCode === null,
            'user_plants' => $userPlants,
            'default_plant' => $defaultPlant,
            'user_id' => $user->id,
        ]);

        // Also set as request input for easy access
        $request->merge([
            '_plant_code' => $resolvedCode,
            '_plant_context' => true,
        ]);

        return $next($request);
    }

    /**
     * Extract plant_id dari request
     *
     * Priority:
     * 1. X-Plant-Id header (untuk API calls)
     * 2. id_plant query parameter
     * 3. id_plant in request body
     * 4. User's default plant from m_plant_user
     */
    protected function extractPlantId(Request $request): mixed
    {
        // 1. Header (for API consistency)
        $plantId = $request->header('X-Plant-Id');
        if ($plantId) {
            return $plantId;
        }

        // 2. Query parameter
        $plantId = $request->query('id_plant');
        if ($plantId !== null) {
            return $plantId;
        }

        // 3. Request body
        $plantId = $request->input('id_plant');
        if ($plantId !== null) {
            return $plantId;
        }

        // 4. Check user's default plant from m_plant_user
        // Only use if no plant specified in request
        $user = $request->user();
        if ($user && $user->id_plant) {
            return $user->id_plant;
        }

        // Return 0 for "all plants" if no other choice
        return 0;
    }
}

/**
 * PlantScopeMiddleware - Middleware to enforce plant scoping
 *
 * Difference with PlantContextMiddleware:
 * - PlantScopeMiddleware will reject the request if no plant is specified
 * - For operations that REQUIRE plant scope
 *
 * Usage:
 * Route::middleware(['auth:sanctum', 'plant.scope'])->group(function () {
 *     // Operations that require plant scope
 * });
 */
class PlantScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error('Authentication required', 401);
        }

        $plantId = $request->query('id_plant') ?? $request->input('id_plant') ?? $request->header('X-Plant-Id');

        // Check if plant_id is provided
        if ($plantId === null || $plantId === '' || $plantId === '0') {
            return ApiResponse::error('Plant ID is required for this operation', 422, ['error_code' => 'PLANT_REQUIRED']);
        }

        // Resolve and validate
        $resolvedCode = PlantContextService::resolvePlantId($plantId, $user->id);

        if (!$resolvedCode) {
            return ApiResponse::error('Invalid or inaccessible plant', 403, ['error_code' => 'PLANT_INVALID']);
        }

        // Inject resolved plant code
        $request->attributes->set('plant_code', $resolvedCode);
        $request->merge(['_plant_code' => $resolvedCode]);

        return $next($request);
    }
}