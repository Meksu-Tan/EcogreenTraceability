<?php

declare(strict_types=1);

namespace Modules\Manufacturer\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 'm_manufacturer';

    protected $primaryKey = 'id_manufacturer';

    protected $fillable = [
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false;
}
