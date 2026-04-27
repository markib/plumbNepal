<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProviderContract;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Providers\CloudProvider;
use Illuminate\Support\Facades\Log;

class AiRouter
{
    public function getProvider(): AiProviderContract
    {
        // 1. Try Ollama (Local)
        if ($this->isLocalAvailable()) {
            return new OllamaProvider();
        }

        // 2. Fallback to Cloud
        Log::warning('Local AI service unavailable, switching to Cloud Provider.');
        return new CloudProvider(config('services.ai.default_cloud_driver', 'gemini'));
    }

    private function isLocalAvailable(): bool
    {
        try {
            // Quick ping to Ollama
            $ch = curl_init('http://localhost:11434');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
