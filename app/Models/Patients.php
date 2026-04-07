<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Patients extends Model
{
    protected $fillable = [
        'mrn_number',
        'patient_name',
        'birth_date',
        'provider_name',
        'medicaid_id',
        'medicaid_plan',
        'created_by'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function birthDate(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? Carbon::parse($value)->format('M-d-Y') : '');
    }

    protected function createdAt(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? Carbon::parse($value)->format('M-d-Y H:i') : '');
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? Carbon::parse($value)->format('M-d-Y H:i') : '');
    }
}
