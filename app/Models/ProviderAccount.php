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

    protected $hidden = ['api_key'];

    public function aiConfigs()
    {
        return $this->hasMany(AiConfig::class);
    }
}
