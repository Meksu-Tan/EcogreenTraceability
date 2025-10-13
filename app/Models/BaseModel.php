<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    use HasFactory;

    protected static $idPlant = null;

    /**
     * Set plant context from the currently authenticated user.
     * - Admin/super-admin => 0 (means "all plants")
     * - Other users => first id_plant found in m_plant_user for that user
     */
    public static function setPlantContext(): void
    {
        self::$idPlant = null;

        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        if (method_exists($user, 'hasRole') && ($user->hasRole('super-admin') || $user->hasRole('admin'))) {
            // 0 means "show all plants"
            self::$idPlant = 0;
            return;
        }

        $row = DB::selectOne('SELECT id_plant FROM m_plant_user WHERE user_id = ? LIMIT 1', [$user->id]);

        self::$idPlant = $row->id_plant ?? null;
    }

    public static function getPlantId()
    {
        return self::$idPlant;
    }

    public static function resolvePlant($request)
    {
        $idPlant = self::getPlantId();

        $selectedPlant = $request->input('plant') ?? session('selected_plant');

        if (!empty($selectedPlant) && $selectedPlant !== '') {
            $idPlant = $selectedPlant;
        }

        return $idPlant;
    }
}
