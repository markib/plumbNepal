<?php

return [
    'default' => env('AI_DEFAULT_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
        ],
    ],
];
