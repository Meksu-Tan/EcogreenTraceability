<?php
declare(strict_types=1);
namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class PlantContextMiddleware
{
    public function __construct(
        private readonly PlantContextServiceInterface $plantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $plantId = $this->extractPlantId($request);
        $resolvedCode = $this->plantContext->resolvePlantId($plantId, $user->id);
        $userPlants = $this->plantContext->getUserPlants($user->id);
        $defaultPlant = $this->plantContext->getDefaultPlant($user->id);

        $request->attributes->set('plant_context', [
            'plant_code' => $resolvedCode,
            'plant_id' => $plantId,
            'is_all_plants' => $resolvedCode === null,
            'user_plants' => $userPlants,
            'default_plant' => $defaultPlant,
            'user_id' => $user->id,
        ]);

        $request->merge([
            '_plant_code' => $resolvedCode,
            '_plant_context' => true,
        ]);

        return $next($request);
    }

    protected function extractPlantId(Request $request): mixed
    {
        $plantId = $request->header('X-Plant-Id');
        if ($plantId) {
            return $plantId;
        }

        $plantId = $request->query('id_plant');
        if ($plantId !== null) {
            return $plantId;
        }

        $plantId = $request->input('id_plant');
        if ($plantId !== null) {
            return $plantId;
        }

        $user = $request->user();
        if ($user && $user->id_plant) {
            return $user->id_plant;
        }

        return 0;
    }
}

class PlantScopeMiddleware
{
    public function __construct(
        private readonly PlantContextServiceInterface $plantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error('Authentication required', 401);
        }

        $plantId = $request->query('id_plant') ?? $request->input('id_plant') ?? $request->header('X-Plant-Id');

        if ($plantId === null || $plantId === '' || $plantId === '0') {
            return ApiResponse::error('Plant ID is required for this operation', 422, ['error_code' => 'PLANT_REQUIRED']);
        }

        $resolvedCode = $this->plantContext->resolvePlantId($plantId, $user->id);

        if (!$resolvedCode) {
            return ApiResponse::error('Invalid or inaccessible plant', 403, ['error_code' => 'PLANT_INVALID']);
        }

        $request->attributes->set('plant_code', $resolvedCode);
        $request->merge(['_plant_code' => $resolvedCode]);

        return $next($request);
    }
}
