<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotations';

    protected $fillable = [
        'translation_id',
        'version',
        'pdf_path',
        'created_by',
        'updated_by',
        'status',
        'notes',
        'cost_table',
        'approved',
        'approved_by',
        'approved_at',
        'rejected',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    public function translation()
    {
        return $this->belongsTo(Translation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvedBy(){
        return $this->belongsTo(User::class, 'approved_by');
    }
}
