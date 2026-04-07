<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientTypeFilter extends Model
{
    protected $table = 'subclient_type_filters';

    protected $fillable = [
        'subclient_type_id',
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

    public function subclientType(){
        return $this->belongsTo(SubClientType::class,'subclient_type_id');
    }
    

}
