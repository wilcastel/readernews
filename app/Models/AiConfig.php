<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConfig extends Model
{
    protected $fillable = [
        'name', 'provider', 'base_url', 'api_key', 'model_id', 'is_active', 'mode',
        'input_price', 'output_price', 'description', 'cantaprox', 'provider_account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
        'input_price' => 'decimal:8',
        'output_price' => 'decimal:8',
        'cantaprox' => 'integer',
    ];

    protected $hidden = ['api_key'];

    protected $appends = ['resolved_base_url'];

    public function providerAccount()
    {
        return $this->belongsTo(ProviderAccount::class);
    }

    public function getResolvedApiKeyAttribute(): ?string
    {
        if ($this->providerAccount) {
            return $this->providerAccount->api_key;
        }

        return $this->api_key;
    }

    public function getResolvedBaseUrlAttribute(): ?string
    {
        if ($this->providerAccount) {
            return $this->providerAccount->base_url;
        }

        return $this->base_url;
    }
}
