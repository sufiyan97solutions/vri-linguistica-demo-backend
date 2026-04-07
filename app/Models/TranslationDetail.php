<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationDetail extends Model
{
    protected $table = 'translation_details';

    protected $fillable = [
        'translation_id',
        'due_date',
        'requester_phone',
        'requester_email',
        'comment',
        'formatting',
        'rush',
        'client_request_editing_reason',
        'translation_decline_reason',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
    ];
    
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
