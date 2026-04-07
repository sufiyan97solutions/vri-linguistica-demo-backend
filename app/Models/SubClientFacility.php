<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientFacility extends Model
{
    protected $table = 'subclient_facilities';

    protected $fillable = [
        'subclient_id',
        'facility_id'
    ];


}
