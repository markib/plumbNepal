<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\AI\Facades\AI;
use App\Services\AI\AiRouter;
use Illuminate\Support\Facades\Http;

class AiService
{

    public function __construct( protected AiRouter $router,
        protected AiStorageService $storage)
    {
    }

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
        try {
            $prompt = <<<PROMPT
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

            // $response = AI::driver('ollama')->generate($prompt);

            // $content = $response->text();

            // $data = json_decode($content, true);
             // ✅ FIX: Direct Ollama HTTP call (reliable)
        $response = Http::withoutVerifying()->withToken(env('NVIDIA_API_KEY'))
                ->withOptions([
                    'force_ip_resolve' => 'v4', // 👈 Force IPv4
                ])
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout(30)->post(
                env('NVIDIA_BASE_URL') . '/chat/completions',
                [
                    'model' => env('NVIDIA_MODEL'),
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $message]
                    ],
                    'temperature' => 1,
                    'max_tokens' => 16384,
                    'top_p' => 0.95,
                    'extra_body' => ['chat_template_kwargs' => ['thinking' => true, 'reasoning_effort' => 'high']],
                    'stream' => false
                ]
            );

        
            if ($response->failed()) {
                throw new Exception("NVIDIA API Error: " . $response->body());
            }
        
            // Use the null-coalescing operator to ensure we always pass a string
            $content = $response->json('choices.0.message.content') ?? '';
            if (empty($content)) {
                throw new Exception("NVIDIA returned an empty response.");
            }
            $reasoning = $response->json('choices.0.message.reasoning_content');
            if ($reasoning) {
                Log::info("AI Reasoning: " . $reasoning);
            }

        // ✅ FIX: safer JSON extraction
        $data = $this->extractJson($content);


            if (!$this->isValid($data)) {
                if ($retries > 0) {
                    Log::warning("AI returned malformed JSON, retrying...", [
                        'response' => $content
                    ]);

                    return $this->analyze($message, $retries - 1);
                }

                Log::error("AI returned invalid JSON after retries", [
                    'response' => $content
                ]);

                throw new Exception("Invalid AI response format");
            }

      
            return $data;

        } catch (Exception $e) {
            Log::error("AI Diagnostic Failure: " . $e->getMessage());

            throw new Exception("The AI analysis service is currently unavailable.");
        }
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