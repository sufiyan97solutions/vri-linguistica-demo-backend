<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = [
        'appid',
        'account_id',
        'interpreter_id',
        'type',
        'datetime',
        'date',
        'start_time',
        'language_id',
        'duration',
        'vendor_id',
        'status',
        'deleted_at',
        'created_by',
        'assigned_by',
        'token'
    ];


    public function vendor(){
        return $this->belongsTo(User::class,'vendor_id');
    }
   

    public function accounts()
    {
        return $this->belongsTo(SubClientType::class, 'account_id', 'id');
    }

    public function interpreter()
    {
        return $this->belongsTo(Interpreter::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function invites()
    {
        return $this->hasMany(AppointmentInvites::class);
    }

    public function appointmentAssign()
    {
        return $this->hasOne(AppointmentAssign::class);
    }

    public function appointmentDetails()
    {
        return $this->hasOne(AppointmentDetail::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

}
