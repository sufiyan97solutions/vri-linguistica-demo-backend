<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interpreter extends Model
{
    protected $table = 'interpreters';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'state_id',
        'city_id',
        'zip_code',
        'address',
        'vendor_id',
        'is_translator'
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    
    public function city(){
        return $this->belongsTo(City::class);
    }
    
    public function state(){
        return $this->belongsTo(State::class);
    }

    public function languages(){
        return $this->hasMany(InterpreterLanguage::class);
    }

    public function vendor(){
        return $this->hasOne(User::class, "id", 'vendor_id');
    }

    public function interpreterRates(){
        return $this->belongsTo(InterpreterRates::class, 'id','interpreter_id');
    }



}
