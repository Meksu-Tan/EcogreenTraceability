<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    protected static $idPlant = null;

    public static function setPlantContext(?Request $request = null): void
    {
        self::$idPlant = self::resolvePlant($request);
    }

    public static function getPlantId()
    {
        return self::$idPlant;
    }

    public static function resolvePlant($request = null)
    {
        $requestPlant = null;

        if ($request instanceof Request) {
            $hasPlantInput = $request->has('id_plant') || $request->has('plant');
            $requestPlant = $request->input('id_plant') ?? $request->input('plant');

            if ($hasPlantInput && ($requestPlant === null || $requestPlant === '')) {
                return 0;
            }
        }

        if ($requestPlant !== null && $requestPlant !== '') {
            return self::normalizePlantId($requestPlant);
        }

        $user = Auth::user();

        if (!$user) {
            return 0;
        }

        if (method_exists($user, 'hasRole') && ($user->hasRole('super-admin') || $user->hasRole('admin'))) {
            return 0;
        }

        if (!empty($user->id_plant)) {
            return self::normalizePlantId($user->id_plant);
        }

        $row = DB::selectOne('SELECT id_plant FROM m_plant_user WHERE user_id = ? LIMIT 1', [$user->id]);

        return isset($row->id_plant) ? self::normalizePlantId($row->id_plant) : 0;
    }

    protected static function normalizePlantId($plantId)
    {
        if ($plantId === null || $plantId === '' || (string) $plantId === '0') {
            return 0;
        }

        try {
            $plant = DB::table('m_plant')
                ->where('code_3', $plantId)
                ->orWhere('id_plant', $plantId)
                ->value('code_3');

            return $plant ?: $plantId;
        } catch (\Throwable) {
            return $plantId;
        }
    }
}
