<?php declare(strict_types=1);
namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'eudr_ts';
    protected $table      = 'm_supplier';
    protected $primaryKey = 'id_supplier';

    protected $fillable = [
        'code',
        'batch_code',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function storageTank()
    {
        return $this->belongsTo(\Modules\Storage\Models\StorageTank::class, 'type', 'id_sloc');
    }
}
