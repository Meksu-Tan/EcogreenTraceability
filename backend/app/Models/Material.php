<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table      = 'm_material';
    protected $primaryKey = 'id_material';

    protected $fillable = [
        'code',
        'code_noneudr',
        'description',
        'type',
        'yield',
        'qtf_feed',
        'qtf_rundown',
        'id_feed',
        'id_rundown',
        'status_packaging',
        'code_matl_supplier',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function packagings()
    {
        return $this->hasMany(MaterialPackaging::class, 'id_material', 'id_material');
    }
}
