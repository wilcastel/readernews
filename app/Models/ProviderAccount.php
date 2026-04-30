<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderAccount extends Model
{
    protected $fillable = [
        'provider', 'label', 'email', 'api_key', 'base_url', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
    ];

    public function aiConfigs()
    {
        return $this->hasMany(AiConfig::class);
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['api_key'] = $this->api_key ? str_repeat('•', 8).substr($this->api_key, -4) : null;

        return $array;
    }
}
