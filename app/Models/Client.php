<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'purchase_contact',
        'phone',
        'start_date',
        'end_date',
        'notes'
    ];

    public function billingInfo()
    {
        return $this->hasOne(ClientBillingInfo::class);
    }

    public function serviceRates()
    {
        return $this->hasMany(ClientBillingInfo::class);
    }

}
