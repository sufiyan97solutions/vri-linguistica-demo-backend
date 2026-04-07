<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    protected $fillable = ['name', 'url', 'page_name'];

    // Relationship with Permissions
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'access_id');
    }
}
