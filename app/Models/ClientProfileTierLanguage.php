<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfileTierLanguage extends Model
{
    protected $table = 'client_profile_tier_languages'; // Table ka naam

    protected $fillable = [
        'client_tier_id',
        'language_id',
    ];

    // Relationship with ClientProfileTier
    public function clientProfileTier()
    {
        return $this->belongsTo(ClientProfileTier::class, 'client_tier_id');
    }

    // Relationship with Language
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
