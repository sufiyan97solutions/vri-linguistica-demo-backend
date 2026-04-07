<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubclientVriOnDemandLanguage extends Model
{
    protected $fillable = [
        'subclient_id',
        'language_id',
    ];
}
