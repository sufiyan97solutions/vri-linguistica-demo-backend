<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationTargetLanguage extends Model
{
    protected $table = 'translation_target_languages';

    protected $fillable = [
        'translation_id',
        'language_id',
    ];

    public function language(){
        return $this->belongsTo(Language::class);
    }
}
