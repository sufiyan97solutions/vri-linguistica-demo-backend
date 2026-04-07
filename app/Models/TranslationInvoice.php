<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationInvoice extends Model
{
    protected $table = 'translation_invoices';

    protected $fillable = [
        'translation_id',
        'account_id',
        'interpreter_id',
        'invoice_number',
        'invoice_path',
        'amount',
        'status',
        'paid_at',
        'updated_by',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
