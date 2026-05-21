<?php

namespace Modules\Storage\Models;

use Illuminate\Database\Eloquent\Model;

class StorageTank extends Model
{
    protected $table      = 'm_sloc';
    protected $primaryKey = 'id_sloc';

    protected $fillable = [
        'code_2',
        'code_3',
        'code_4',
        'id_plant',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(StorageDetail::class, 'id_sloc', 'id_sloc');
    }
}
