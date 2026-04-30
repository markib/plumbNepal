<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProviderContract;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Providers\CloudProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRouter
{
    public function getProvider(): AiProviderContract
    {
        $priority = config('ai.priority', ['ollama', 'openai', 'gemini', 'nvidia']);

        foreach ($priority as $key) {
            try {
                // 1. LOCAL FIRST - Ollama
                if ($key === 'ollama') {
                    if ($this->isLocalAvailable()) {
                        Log::info("Selected provider: ollama");
                        return (new OllamaProvider('ollama'));
                    }

                    Log::warning("Ollama not available");
                    continue;
                }

                // 2. Cloud providers
                if (in_array($key, ['gemini', 'nvidia', 'openai'])) {
                    return new CloudProvider($key);
                }
            } catch (\Exception $e) {
                Log::warning("Provider $key failed: " . $e->getMessage());
                continue;
            }
        }

        throw new \Exception('No AI providers are available.');
    }

    /**
     * Get a specific provider by key (for fallback iteration).
     */
    public function getProviderFor(string $key): AiProviderContract
    {
        if ($key === 'ollama') {
            if (!$this->isLocalAvailable()) {
                throw new \Exception("Ollama not available");
            }
            return new OllamaProvider('ollama');
        }

        if (in_array($key, ['gemini', 'nvidia', 'openai'])) {
            return new CloudProvider($key);
        }

        throw new \Exception("Unknown provider: $key");
    }

    private function isLocalAvailable(): bool
    {
        try {
            $response = Http::timeout(2)
                ->retry(1, 100)
                ->get(env('OLLAMA_API_URL', 'http://localhost:11434') . '/api/tags');

            return $response->ok();
        } catch (\Exception $e) {
            Log::warning('Ollama not available: ' . $e->getMessage());
            return false;
        }
    }

    public function generatePrompt(string $message): string
    {
        return <<<PROMPT
You are an expert plumbing consultant for the Nepal market.

Analyze the following user issue and return ONLY a strict JSON object.

Schema:
{
  "issue_type": "pipe_leak | blockage | drainage | installation",
  "urgency": "high | medium | low",
  "estimated_price_min": integer,
  "estimated_price_max": integer,
  "recommended_service": "short name",
  "confidence": float,
  "summary": "short explanation"
}

Rules:
- No markdown
- No extra text
- If unclear → confidence < 0.4
- If not plumbing → confidence = 0.0

User issue: {$message}
PROMPT;
    }
}
