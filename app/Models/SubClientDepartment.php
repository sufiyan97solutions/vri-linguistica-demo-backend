<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientDepartment extends Model
{
    protected $table = 'subclient_departments';

    protected $fillable = [
        'subclient_id',
        'departments_id'
    ];

    public function department(){
        return $this->belongsTo(Department::class, 'departments_id');
    }

}
