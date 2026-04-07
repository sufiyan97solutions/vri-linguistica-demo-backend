<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBillingInfo extends Model
{
    protected $fillable = [
        'client_id',
        'contact',
        'phone',
        'fax',
        'address',
        'state_id',
        'city_id',
        'zip_code',
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
