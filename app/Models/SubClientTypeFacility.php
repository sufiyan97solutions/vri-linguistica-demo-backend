<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientTypeFacility extends Model
{
    protected $table = 'subclient_types_facilities';

    protected $fillable = [
        'type_id',
        'abbreviation',
        'address',
        'state_id',
        'city_id',
        'zipcode',
        'phone',
        'status',
    ];

    public function state()
    {
        return $this->belongsTo(State::class ,'state_id' ,'id');
    }
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
