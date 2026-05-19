<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $table      = 'm_plant';
    protected $primaryKey = 'id_plant';

    protected $fillable = [
        'code_2',
        'code_3',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true; // Most modern tables have timestamps, but I'll check if they are managed manually
}
