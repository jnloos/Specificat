<?php

namespace App\Events\Debug\Prompts;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromptSuccess implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $pid,
        public string $time,
        public string $response,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('nativephp'),
        ];
    }
}
