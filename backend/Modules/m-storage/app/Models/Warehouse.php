<?php declare(strict_types=1);
namespace Modules\Storage\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $connection = 'eudr_ts';
    protected $table      = 'm_warehouse';
    protected $primaryKey = 'id_warehouse';

    protected $fillable = [
        'id_batch',
        'code',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;
}
