<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationTranslatedFile extends Model
{
    protected $table = 'translation_translated_files';

    protected $fillable = [
        'translation_id',
        'file_name',
        'file_path',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'status'
    ];

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
