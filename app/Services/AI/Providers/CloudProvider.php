<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudProvider implements AiProviderContract
{
    protected string $driver;

    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? config('ai.default', 'openai');
    }

    public function getName(): string
    {
        return $this->driver; 
    }

    public function generate(string $prompt): string
    {

        // Example for Google Gemini or OpenAI
        // Adjust logic based on the driver
        // $url = $this->driver === 'gemini' 
        //     ? 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . config('ai.providers.gemini.api_key')
        //     : 'https://api.openai.com/v1/chat/completions';
        $providers = $this->getFallbackOrder();

        foreach ($providers as $provider) {
            try {
        // Simplified implementation:
        Log::info("Trying provider: {$provider}");

                $result = match ($provider) {
            'openai' => $this->openai($prompt),
            'gemini' => $this->gemini($prompt),
            'nvidia' => $this->nvidia($prompt),
            default => throw new \Exception("Unsupported cloud provider: {$provider}")
        };
                Log::info("Success with provider: {$provider}");

                return $result;
            } catch (\RuntimeException $e) {
                // 🔥 quota-specific fallback
                if ($e->getMessage() === 'OPENAI_QUOTA_EXCEEDED') {
                    Log::warning("OpenAI quota exceeded → switching...");
                    continue;
                }

                Log::warning("Provider runtime error: {$provider}");
            } catch (\Exception $e) {
                Log::warning("Provider failed: {$provider} - " . $e->getMessage());
        }

        throw new \Exception('All cloud providers failed');
        }
    }

    // ==============================
    // 🔷 OPENAI
    // ==============================
    private function openai(string $prompt): string
    {
        $config = config('ai.providers.openai');

        $res = Http::timeout(30)
            ->withToken($config['api_key'])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $config['model'] ?? 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
            ]);

        if (!$res->ok()) {
            $body = $res->json();
            // ✅ Detect quota issue
            if (data_get($body, 'error.code') === 'insufficient_quota') {
                throw new \RuntimeException('OPENAI_QUOTA_EXCEEDED');
            }

            throw new \Exception('OpenAI failed: ' . $res->body());
        }

        return $this->clean(
            data_get($res->json(), 'choices.0.message.content', '')
        );
    }

    // ==============================
    // 🔷 GEMINI
    // ==============================
    private function gemini(string $prompt): string
    {
        $config = config('ai.providers.gemini');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/"
            . ($config['model'] ?? 'gemini-1.5-flash')
            . ":generateContent?key=" . $config['api_key'];

        $res = Http::timeout(30)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if (!$res->ok()) {
            throw new \Exception('Gemini failed: ' . $res->body());
        }

        return $this->clean(
            data_get($res->json(), 'candidates.0.content.parts.0.text', '')
        );
    }

    // ==============================
    // 🔷 NVIDIA
    // ==============================
    private function nvidia(string $prompt): string
    {
        $config = config('ai.providers.nvidia');

        $res = Http::timeout(60)
            ->withToken($config['api_key'])
            ->post('https://integrate.api.nvidia.com/v1/chat/completions', [
                'model' => $config['model'] ?? 'meta/llama3-8b-instruct',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

        if (!$res->ok()) {
            throw new \Exception('NVIDIA failed: ' . $res->body());
        }

        return $this->clean(
            data_get($res->json(), 'choices.0.message.content', '')
        );
    }

    // ==============================
    // 🔧 CLEAN RESPONSE
    // ==============================
    private function clean(string $text): string
    {
        // Remove ```json blocks if model adds them
        $text = preg_replace('/```json|```/', '', $text);

        return trim($text);
    }

    private function getFallbackOrder(): array
    {
        // Use the passed driver as primary, then fall back to others
        $allProviders = ['openai', 'gemini', 'nvidia'];
        $primary = $this->driver;
        
        // If primary is not a cloud provider, use default order
        if (!in_array($primary, $allProviders)) {
            return $allProviders;
        }
        
        // Put primary first, then the rest
        $others = array_filter($allProviders, fn($p) => $p !== $primary);
        return array_merge([$primary], $others);
    }
}
