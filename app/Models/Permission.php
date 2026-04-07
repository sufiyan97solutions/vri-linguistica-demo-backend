<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['group_id', 'access_id', 'status'];

    // Relationship with PermissionGroup
    public function group()
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    // Relationship with Access
    public function access()
    {
        return $this->belongsTo(Access::class, 'access_id');
    }
}
