<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationFile extends Model
{
    protected $table = 'translation_files';

    protected $fillable = [
        'translation_id',
        'original_file',
        'original_file_name',
        'password',
        'word_count',
        'translated_file',
        'file_status',
        'amount'
    ];
}
