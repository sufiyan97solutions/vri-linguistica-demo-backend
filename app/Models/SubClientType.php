<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientType extends Model
{
    protected $table = 'subclient_types';

    protected $fillable = [
        'user_id',
        'phone',
        'normal_hour_start_time',
        'normal_hour_end_time',
        'after_hour_start_time',
        'after_hour_end_time',
        'rush_fee',
        'incremental',
        'grace_period',
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function facilities(){
        return $this->hasMany(SubClientTypeFacility::class, "type_id");
    }

    public function departments(){
        return $this->hasMany(SubClientTypeDepartment::class, 'type_id');
    }

    public function interpretationRates()
    {
        return $this->hasMany(SubClientTypeInterpretationRate::class , "subclient_id" , 'id');
    }
    public function appointments(){
        return $this->hasMany(Appointment::class,'account_id');
    }
    public function vriOnDemandLanguages()
    {
        return $this->hasMany(SubclientVriOnDemandLanguage::class, 'subclient_id');
    }

    public function vriPrescheduledLanguages()
    {
        return $this->hasMany(SubclientVriPrescheduledLanguage::class, 'subclient_id');
    }
}
