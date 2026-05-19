<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialPackaging extends Model
{
    protected $table      = 'm_material_pck';
    protected $primaryKey = 'id_materialpck';

    protected $fillable = [
        'code',
        'code_noneudr',
        'description',
        'id_material',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function material()
    {
        return $this->belongsTo(Material::class, 'id_material', 'id_material');
    }
}
