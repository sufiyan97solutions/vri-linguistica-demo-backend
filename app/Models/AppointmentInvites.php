<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentInvites extends Model
{

    protected $table = 'appointment_invitees';

    protected $fillable = [
        'appointment_id',
        'interpreter_id',
        'status',
        'token'
    ];
    
    public function interpreter(){
        return $this->belongsTo(Interpreter::class);
    }
    
    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
    

}
