<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | supported: "ollama", "openrouter", "openai"
    |
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration
    |--------------------------------------------------------------------------
    */
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:4b'),
        'bridge_path' => env('OLLAMA_BRIDGE_PATH', 'scripts/ollama-bridge.cjs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter Configuration
    |--------------------------------------------------------------------------
    */
    'openrouter' => [
        'base_url' => 'https://openrouter.ai/api/v1/chat/completions',
        'api_key' => env('OPENROUTER_API_KEY', ''),
        'model' => env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-exp:free'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic OpenAI Configuration (LM Studio, LocalAI, etc)
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'http://localhost:1234/v1/chat/completions'),
        'api_key' => env('OPENAI_API_KEY', 'lm-studio'),
        'model' => env('OPENAI_MODEL', 'local-model'),
    ],
];
