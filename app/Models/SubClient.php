<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClient extends Model
{
    protected $table = 'subclients';

    protected $fillable = [
        'name',
        // 'client_id',
        // 'user_id',
        'type_id',
        'status',
        'est_duration'
    ];

    // public function user(){
    //     return $this->belongsTo(User::class,'user_id');
    // }
    
    // public function client(){
    //     return $this->belongsTo(User::class,'client_id');
    // }
    
    public function type(){
        return $this->belongsTo(User::class,'type_id');
    }

    public function filters(){
        return $this->hasOne(SubClientFilter::class,'subclient_id');
    }

    public function dynamicFields(){
        return $this->hasMany(SubClientDynamicFields::class,'subclient_id');
    }


    public function facilities(){
        return $this->hasMany(SubClientFacility::class, "subclient_id");
    }
    public function departments(){
        return $this->hasMany(SubClientDepartment::class, 'subclient_id');
    }

    
    public function minDurations(){
        return $this->hasMany(MinimumDuration::class,'subclient_id');
    }

}
