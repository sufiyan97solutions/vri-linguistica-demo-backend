<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentAssign extends Model
{

    protected $table = 'appointment_assigns';

    protected $fillable = [
        'appointment_id',
        'interpreter_id',
        'checkin_date',
        'checkin_time',
        'checkout_date',
        'checkout_time',
        'notes',
        'comments'
    ];
    
    public function interpreter(){
        return $this->belongsTo(Interpreter::class);
    }
    

}
