<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{

    protected $table = 'invoices';

    protected $fillable = [
       'appointment_id',
        'total_hours',
        'rate',
        'rate_unit',
        'min_duration',
        'duration_unit',
        'incremental',
        'extra_duration',
        'total_amount',
        'rush_fee',
        'patient_first_name',
        'patient_last_name',
        'notes',
        'verified'
    ];

    // public function user(){
    //     return $this->belongsTo(User::class,'user_id');
    // }
    
    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
