<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientServiceRate extends Model
{
    protected $fillable = [
        'client_id',
        'otp_spanish',
        'otp_other',
        'appointments_spanish',
        'appointments_other',
        'translations_spanish',
        'translations_other',
    ];
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
