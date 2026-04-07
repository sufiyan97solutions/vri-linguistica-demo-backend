<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentLog extends Model
{
    // use SoftDeletes;

    protected $table = 'appointment_logs';

    protected $fillable = [
        'appointment_id',
        'date',
        'time',
        'event',
        'user_id',
        'notes',
    ];

    // public function user(){
    //     return $this->belongsTo(User::class,'user_id');
    // }
    
    public function user(){
        return $this->belongsTo(User::class);
    }

}
