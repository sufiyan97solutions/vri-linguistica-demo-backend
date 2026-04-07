<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterpreterRates extends Model
{
    protected $table = 'interpreter_rates';

    protected $fillable = [
        'interpreter_id',
        'normal_hour_start_time',
        'normal_hour_end_time',
        'after_hour_start_time',
        'after_hour_end_time',
        'grace_period',
        'opi_normal_rate',
        'vri_normal_rate',
        'inperson_normal_rate',
        'opi_normal_rate_time_unit',
        'vri_normal_rate_time_unit',
        'inperson_normal_rate_time_unit',
        'opi_normal_mins',
        'vri_normal_mins',
        'inperson_normal_mins',
        'opi_normal_min_time_unit',
        'vri_normal_min_time_unit',
        'inperson_normal_min_time_unit',
        'opi_after_rate',
        'vri_after_rate',
        'inperson_after_rate',
        'opi_after_rate_time_unit',
        'vri_after_rate_time_unit',
        'inperson_after_rate_time_unit',
        'opi_after_mins',
        'vri_after_mins',
        'inperson_after_mins',
        'opi_after_min_time_unit',
        'vri_after_min_time_unit',
        'inperson_after_min_time_unit',
        'spanish_translation_rate',
        'other_translation_rate',
        'spanish_formatting_rate',
        'other_formatting_rate'
    ];

}
