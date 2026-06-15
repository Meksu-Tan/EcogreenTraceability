<?php declare(strict_types=1);
namespace Modules\Storage\Models;

use Illuminate\Database\Eloquent\Model;

class StorageDetail extends Model
{
    protected $connection = 'eudr_ts';
    protected $table      = 'm_sloc_detail';
    protected $primaryKey = 'id_sloc_tail';

    protected $fillable = [
        'id_sloc',
        'tf_number',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;

    public function tank()
    {
        return $this->belongsTo(StorageTank::class, 'id_sloc', 'id_sloc');
    }
}
