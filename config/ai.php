<?php

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'ollama'),
    'priority' => [
        'ollama',
        'openai',
        'gemini',
        'nvidia',
    ],
    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4-0613'),
            'timeout' => env('OPENAI_TIMEOUT', 60),
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
            'base_url' => env('GEMINI_BASE_URL', 'https://gemini.googleapis.com/v1'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout' => env('GEMINI_TIMEOUT', 60),
        ],
        'nvidia' => [
            'driver' => 'nvidia',
            'api_key' => env('NVIDIA_API_KEY'),
            'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
            'model' => env('NVIDIA_MODEL', 'nvidia-1.0'),
            'timeout' => env('NVIDIA_TIMEOUT', 60),
        ],        
    ]
];
