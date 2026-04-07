<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentDetail extends Model
{
    // use SoftDeletes;

    protected $table = 'appointment_details';

    protected $fillable = [
        'appointment_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'facility_id',
        'address',
        'department_id',
        'video_link',
        'reschedule_reason',
        'cnc_reason',
        'dnc_reason',
        'cancel_reason',
        'auto_assign',
        'patient_phone',
        'special_instruction',
        'encounter_source',
        'priority_level',
        'patient_id',
        'extra_mileage',
        'extra_mileage_request',
        'room_id',
        'room_name',
        'recording',
        'provider_name',
        'requester_pincode',
        'requester_economic_service'
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function facility()
    {
        return $this->belongsTo(SubClientTypeFacility::class, 'facility_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(SubClientTypeDepartment::class);
    }
    
    public function patient(){
        return $this->hasOne(Patients::class, 'id', 'patient_id');
    }
}
