<?php

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'ollama'),

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
        ],
          // 🟢 Ollama (LOCAL AI)
        'ollama' => [
            'driver' => 'ollama',
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3:8b'),
            'timeout' => env('OLLAMA_TIMEOUT', 60),
        ],
        'gemini' => [
            'driver' => 'gemini',
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'http://127.0.0.1:11434'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout' => env('GEMINI_TIMEOUT', 60),
        ],
    ],
];
