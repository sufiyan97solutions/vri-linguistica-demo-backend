<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TranslationLog extends Model
{
    // use SoftDeletes;

    protected $table = 'translation_logs';

    protected $fillable = [
        'translation_id',
        'date',
        'time',
        'event',
        'user_id',
        'notes',
    ];

    // public function user(){
    //     return $this->belongsTo(User::class,'user_id');
    // }
    
    public function user(){
        return $this->belongsTo(User::class);
    }
    
    public function translation(){
        return $this->belongsTo(Translation::class);
    }

}
