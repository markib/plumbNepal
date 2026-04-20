<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\AI\Facades\AI;

class AiService
{
    public function generateText(string $prompt, array $options = []): string
    {
        try {
            $response = AI::complete(array_merge([
                'model' => $options['model'] ?? 'gpt-4o-mini',
                'prompt' => $prompt,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 250,
            ], $options));

            return trim($response['choices'][0]['text'] ?? '');
        } catch (\Exception $exception) {
            Log::error('AI generation failed: '.$exception->getMessage());
            return 'Unable to generate AI content at this time.';
        }
    }
}
