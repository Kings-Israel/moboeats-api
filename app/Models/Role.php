<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    public $guarded = [];

    /**
     * Get all of the users for the Role
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, RoleUser::class, 'user_id', 'role_id');
    }
}
