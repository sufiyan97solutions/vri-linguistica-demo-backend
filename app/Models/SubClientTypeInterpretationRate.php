<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientTypeInterpretationRate extends Model
{
    protected $table = 'subclient_types_interpretation_rates';

    protected $fillable = [
        'subclient_id',
        'client_tier_id',
        'opi_normal_rate',
        'vri_normal_rate',
        'inperson_normal_rate',
        'opi_normal_rate_time_unit',
        'vri_normal_rate_time_unit',
        'inperson_normal_time_unit',
        'opi_normal_mins',
        'vri_normal_mins',
        'inperson_normal_mins',
        'opi_normal_mins_time_unit' ,
        'vri_normal_mins_time_unit' ,
        'inperson_normal_mins_time_unit',
        'opi_after_rate',
        'vri_after_rate',
        'inperson_after_rate',
        'opi_after_rate_time_unit',
        'vri_after_rate_time_unit',
        'inperson_after_time_unit',
        'opi_after_mins',
        'vri_after_mins',
        'inperson_after_mins',
        'opi_after_mins_time_unit' ,
        'vri_after_mins_time_unit' ,
        'inperson_after_mins_time_unit',
        'spanish_translation_rate',
        'other_translation_rate',
        'spanish_formatting_rate',
        'other_formatting_rate'
    ];

    public function subClient()
    {
        return $this->belongsTo(SubClientType::class, 'id', 'subclient_id');
    }

    public function subClientTier()
    {
        return $this->hasMany(Tiers ::class, 'id', 'client_tier_id');
    }
}
