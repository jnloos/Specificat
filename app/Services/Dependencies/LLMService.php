<?php

namespace App\Services\Dependencies;

use App\Models\Project;

abstract class LLMService
{
    protected LLMClient $aiClient;
    public function __construct(LLMClient $aiClient) {
        $this->aiClient = $aiClient;
    }

    protected abstract function sendPrompt(): array;
    protected abstract function processResponse(array $response): void;

    public static function lockName(string $id): string {
        return "llm:lock:$id";
    }
}
