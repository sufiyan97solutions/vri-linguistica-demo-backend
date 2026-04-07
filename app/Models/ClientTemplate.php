<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientTemplate extends Model
{
    protected $table = 'client_templates';

    protected $fillable = [
        'name',
        'normal_hour_start_time',
        'normal_hour_end_time',
        'after_hour_start_time',
        'after_hour_end_time',
        'grace_period',
        'rush_fee',
        'incremental',
        'status',
    ];

    public function interpretationRates()
    {
        return $this->hasMany(ClientTemplateInterpretationRate::class);
    }
   
}
