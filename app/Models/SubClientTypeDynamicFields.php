<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientTypeDynamicFields extends Model
{
    protected $table = 'subclient_types_dynamic_fields';

    protected $fillable = [
        'subclient_type_id',
        'name',
        'required',
    ];

    public function subclient(){
        return $this->belongsTo(SubClientType::class,'subclient_type_id');
    }
    

}
