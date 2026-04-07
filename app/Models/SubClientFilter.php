<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientFilter extends Model
{
    protected $table = 'subclient_filters';

    protected $fillable = [
        'subclient_id',
        'us_based',
        'non_us_based',
        'english_to_target',
        'spanish_to_target',
        'court_certified',
        'medical_certified',
        'us_based_locked',
        'non_us_based_locked',
        'english_to_target_locked',
        'spanish_to_target_locked',
        'court_certified_locked',
        'medical_certified_locked',
    ];

    public function subclient(){
        return $this->belongsTo(SubClient::class,'subclient_id');
    }
    

}
