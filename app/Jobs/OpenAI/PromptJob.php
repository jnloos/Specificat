<?php

namespace App\Jobs\OpenAI;

use App\Services\OpenAI\KeyForOpenAI;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use OpenAI;

class PromptJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;

    public function __construct(
        private readonly string $resultKey,
        private readonly string $model,
        private readonly string $prompt,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $client = OpenAI::client(KeyForOpenAI::get());

        $result = $client->responses()->create([
            'model' => $this->model,
            'input' => $this->prompt,
        ])->outputText;

        Cache::put($this->resultKey, $result, now()->addMinutes(10));
    }
}
