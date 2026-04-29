<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider implements AiProviderContract
{
    public function generate(string $prompt): string
    {
        $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
            'model' => 'qwen2.5-coder:7b', // Ensure this model is pulled in Ollama
            'prompt' => $prompt,
            'stream' => false,
            'options' => ['temperature' => 0.2]
        ]);

        if ($response->failed()) {
            Log::error('Ollama Provider failed', ['error' => $response->body()]);
            throw new \Exception('Ollama is unreachable.');
        }

        return $response->json('response') ?? '';
    }
}
