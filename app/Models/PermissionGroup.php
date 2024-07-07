<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PermissionGroup extends Model
{
    use HasFactory;

    /**
     * Get all of the permissions for the PermissionGroup
     */
    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(Permission::class, PermissionGrouping::class, 'permission_group_id', 'id', 'id', 'permission_id');
    }
}
