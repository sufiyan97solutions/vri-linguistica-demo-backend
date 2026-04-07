<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientTypeDepartment extends Model
{
    protected $table = 'subclient_types_departments';

    protected $fillable = [
        'type_id',
        'department_name',
        'facility_id',
        'meeting_place',
        'facility_billing_code',
        'department_billing_code',
        'status',
    ];
    public function facility()
    {
        return $this->belongsTo(SubClientTypeFacility::class);
    }
}
