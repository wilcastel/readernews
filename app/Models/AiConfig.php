<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConfig extends Model
{
    protected $fillable = [
        'name', 'provider', 'base_url', 'api_key', 'model_id', 'is_active', 'mode',
        'input_price', 'output_price', 'description', 'cantaprox'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
        'input_price' => 'decimal:8',
        'output_price' => 'decimal:8',
        'cantaprox' => 'integer'
    ];
}
