<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedInvoice extends Model
{
    use HasFactory;

    protected $table = 'generated_invoices'; // Table name

    protected $fillable = [
        'client_id',
        'invoice_number',
        'invoice_type',
        'total_appointments',
        'billing_date',
        'total_due_bill',
        'status',
    ];

    /**
     * Relationship with SubClientType (Client)
     */
    public function client()
    {
        return $this->belongsTo(SubClientType::class, 'client_id');
    }
}
