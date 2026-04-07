<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'image',
        'status',
        'credentials_send',
        'notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getEmailForPasswordReset()
    {
        return $this->email;
    }
    // public function pools()
    // {
    //     return $this->belongsToMany(Pool::class, 'pool_user', 'user_id', 'pool_id')
    //         ->withTimestamps();
    // }
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function subUser()
    {
        return $this->belongsTo(SubUser::class,'user_id' , "id");
    }

    public function accessTokens()
    {
        return $this->hasMany('App\OauthAccessToken');
    }

    public function vendorRates()
    {
        return $this->hasOne(VendorRates::class, 'vendor_id', 'id');
    }

    public function interpreters(){
        return $this->belongsTo(Interpreter::class);
    }

    public function vendorInterpreters(){
        return $this->hasMany(Interpreter::class, 'vendor_id', 'id');
    }

    public function mainAccount(){
        return $this->hasOne(SubClientType::class);
    }

    public function interpreter(){
        return $this->hasOne(Interpreter::class);
    }
   
}
