<?php

namespace App\Services\AI\Contracts;

interface AiProviderContract
{
    public function generate(string $prompt): string;
}
