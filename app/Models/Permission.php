<?php

namespace App\Models;

use Laratrust\Models\Permission as PermissionModel;
use App\Models\RolePermission;

class Permission extends PermissionModel
{
    //
    public $guarded = [];
}
