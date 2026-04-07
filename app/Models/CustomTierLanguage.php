<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomTierLanguage extends Model
{
    protected $table = 'custom_tier_languages';

    protected $fillable = [
        'custom_tiers_id',
        'language_id',
    ];

    public function customTier()
    {
        return $this->belongsTo(CustomTier::class, 'custom_tiers_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}