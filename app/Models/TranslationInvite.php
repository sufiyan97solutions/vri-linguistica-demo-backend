<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationInvite extends Model
{
    protected $table = 'translation_invites';

    protected $fillable = [
        'translation_id',
        'interpreter_id',
        'status',
        'invited_at',
        'responded_at',
        'notes',
        'token'
    ];
    public function interpreter()
    {
        return $this->belongsTo(Interpreter::class);
    }

    public function translation()
    {
        return $this->belongsTo(Translation::class);
    }

}
