<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\AI\Facades\AI;
use App\Services\AI\AiRouter;
use Illuminate\Support\Facades\Http;

class AiService
{

    public function __construct(
        protected AiRouter $router,
        protected AiStorageService $storage
    ) {}

    // public function generateText(string $prompt, array $options = []): string
    // {
    // try {
    // 1. Get the best available provider
    // $provider = $this->router->getProvider();
    //  // 2. Generate
    // $rawResponse = $provider->generate($this->analyze($prompt, $options));
    //    // 3. Parse & Validate
    // $data = json_decode($rawResponse, true);

    //     // $response = AI::complete(array_merge([
    //     //     'model' => $options['model'] ?? 'gpt-4o-mini',
    //     //     'prompt' => $prompt,
    //     //     'temperature' => $options['temperature'] ?? 0.7,
    //     //     'max_tokens' => $options['max_tokens'] ?? 250,
    //     // ], $options));

    //     // return trim($response['choices'][0]['text'] ?? '');

    //      // 4. Persistence
    // if ($data && $data['confidence'] >= 0.4) {
    //     $this->storage->saveResult($data);
    // }
    // return $data;

    // } catch (Exception $exception) {
    //     Log::error('AI generation failed: ' . $exception->getMessage());
    //     return 'Unable to generate AI content at this time.';
    // }
    // }

    public function analyze(string $message, int $retries = 1): array
    {
        $providers = config('ai.priority', ['ollama', 'openai', 'gemini', 'nvidia']);
        $lastException = null;

        foreach ($providers as $providerKey) {
            try {
                // Get provider for this key
                $provider = $this->router->getProviderFor($providerKey);

                // 2. Generate
                $prompt = $this->getSystemPrompt($message);
                $rawResponse = $provider->generate($prompt);

                // 3. Parse & Validate
                $data = $this->extractJson($rawResponse);

                if (!$this->isValid($data)) {
                    if ($retries > 0) {
                        Log::warning("AI returned malformed JSON, retrying...", [
                            'response' => $rawResponse,
                            'provider' => $providerKey,
                        ]);

                        return $this->analyze($message, $retries - 1);
                    }

                    Log::error("AI returned invalid JSON after retries", [
                        'response' => $rawResponse,
                        'provider' => $providerKey,
                    ]);

                    throw new Exception("Invalid AI response format");
                }

                // Map keys for consistency
                $data['price_min'] = $data['estimated_price_min'] ?? 0;
                $data['price_max'] = $data['estimated_price_max'] ?? 0;
                $data['service'] = $data['recommended_service'] ?? 'General Service';
                $data['raw'] = json_encode($data);

                // 4. Persistence
                if ($data['confidence'] >= 0.4) {
                    $this->storage->saveResult($data);
                }

                return $data;

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Timeout or connection error - try next provider
                Log::warning("Provider $providerKey failed: " . $e->getMessage());
                $lastException = $e;
                continue;
            } catch (\Exception $e) {
                Log::warning("Provider $providerKey error: " . $e->getMessage());
                $lastException = $e;
                continue;
            }
        }

        // All providers failed
        $errorMsg = $lastException 
            ? "All AI providers failed. Last error: " . $lastException->getMessage()
            : "All AI providers are unavailable.";
        
        Log::error("AI Diagnostic Failure: " . $errorMsg);
        throw new Exception($errorMsg);
    }

    private function getSystemPrompt(string $message): string
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

    private function extractJson(string $text): array
    {
        // Remove markdown code blocks if present
        $text = preg_replace('/^```json\s*|```$/m', '', $text);

        // Find the first '{' and last '}'
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false) {
            $jsonContent = substr($text, $start, $end - $start + 1);
            return json_decode($jsonContent, true) ?? [];
        }

        return [];
    }

    private function isValid(?array $data): bool
    {
        return $data !== null && isset(
            $data['issue_type'],
            $data['urgency'],
            $data['confidence'],
            $data['estimated_price_min'],
            $data['estimated_price_max'],
            $data['recommended_service'],
            $data['summary']
        );
    }
}
