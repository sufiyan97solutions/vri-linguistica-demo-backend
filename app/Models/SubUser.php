<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubUser extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'gender',
        'phone',
        'status',
    ];

    // User Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Permission Group Relationship
    public function group()
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }
    
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'group_id','group_id');
    }
}
