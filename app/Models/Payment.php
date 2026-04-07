<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{

    protected $table = 'payments';

    protected $fillable = [
        'payment_user_id',
        'appt_id',
        'payment',
        'status',
        'user_type',
        'date',
        'rate' ,
        'rate_unit' ,
        'min_duration' ,
        'duration_unit' ,
        'extra_duration' ,
        'total_hours' ,
        'extra_mileage' ,
    ];

    public function appointment(){
        return $this->belongsTo(Appointment::class ,'appt_id', 'id');
    }
    public function paymentUser(){
        return $this->belongsTo(User::class ,'payment_user_id', 'id');
    }
}
