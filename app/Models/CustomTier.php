<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomTier extends Model
{
    protected $table = 'custom_tiers';

    protected $fillable = [
        'subclient_id',
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
        'vri_normal_mins_time_unit',
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
        'vri_after_mins_time_unit',
        'inperson_after_mins_time_unit',
    ];

    public function subclient()
    {
        return $this->belongsTo(SubclientType::class, 'subclient_id');
    }
    public function customTierLanguages()
    {
        return $this->hasMany(CustomTierLanguage::class, 'custom_tiers_id', "id");
    }
}
