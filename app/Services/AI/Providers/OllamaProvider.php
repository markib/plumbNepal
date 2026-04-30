<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider implements AiProviderContract
{
    protected string $driver;

    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? 'ollama';
    }
    public function getName(): string
    {
        return $this->driver;
    }
    public function generate(string $prompt): string
    {
        $response = Http::timeout(env('OLLAMA_TIMEOUT', 30))->post(env(
                'OLLAMA_BASE_URL'.'/api/generate','http://localhost:11434/api/generate'), [
            'model' => env('OLLAMA_MODEL', 'qwen2.5-coder:7b'), // Ensure this model is pulled in Ollama
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
