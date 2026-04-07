<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubClientDynamicFields extends Model
{
    protected $table = 'subclient_dynamic_fields';

    protected $fillable = [
        'subclient_id',
        'name',
        'required'
    ];

    public function subclient(){
        return $this->belongsTo(SubClient::class,'subclient_id');
    }
    

}
