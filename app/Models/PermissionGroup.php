<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    protected $fillable = ['name', 'select_all', 'created_by_id', 'status'];

    // Relationship with User
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    // Relationship with Permissions
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'group_id');
    }
}
