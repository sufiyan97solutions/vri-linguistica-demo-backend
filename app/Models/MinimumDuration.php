<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinimumDuration extends Model
{
    protected $table = 'min_durations';

    protected $fillable = [
        'type_id',
        'subclient_id',
        'min_duration',
        'language_id',
        'start_time',
        'end_time',
    ];

    public function type(){
        return $this->belongsTo(SubClientType::class,'type_id');
    }

    public function language(){
        return $this->belongsTo(Language::class);
    }
    

}
