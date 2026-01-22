<?php

namespace Wilcastel\AiManager\Models;

use Illuminate\Database\Eloquent\Model;

class AiConfig extends Model
{
    protected $table = 'ai_configs';

    protected $fillable = [
        'name', 'provider', 'base_url', 'api_key', 'model_id', 'is_active', 'mode'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted' 
    ];
}
