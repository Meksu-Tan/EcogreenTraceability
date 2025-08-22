<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $connection = 'tf_eob_1';
	protected $table = 'modules';
    protected $fillable = [
        'name'
    ];

    public function permission()
    {
        return $this->hasMany('App\Models\Permission');
    }
}
