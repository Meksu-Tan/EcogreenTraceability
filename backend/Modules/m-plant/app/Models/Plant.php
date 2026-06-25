<?php
declare(strict_types=1);
namespace Modules\Plant\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $connection = 'eudr_ts';
    protected $table      = 'm_plant';
    protected $primaryKey = 'id_plant';

    protected $fillable = [
        'code',
        'code_2',
        'code_3',
        'id_sloc',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;
}
