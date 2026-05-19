<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_plant',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'user',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key', 'user_id'),
            config('permission.column_names.role_pivot_key', 'role_id')
        );
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.permission'),
            'user',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.model_morph_key', 'user_id'),
            config('permission.column_names.permission_pivot_key', 'permission_id')
        );
    }
}
