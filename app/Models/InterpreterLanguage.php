<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterpreterLanguage extends Model
{
    protected $table = 'interpreter_languages';

    protected $fillable = [
        'interpreter_id',
        'language_id',
    ];

    public function language(){
        return $this->belongsTo(Language::class);
    }


}
