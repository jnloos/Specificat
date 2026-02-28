<?php

namespace App\Events\Debug\Prompts;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromptRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $pid,
        public string $time,
        public string $model = '',
        public string $input = '',
        public array $attachments = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('nativephp'),
        ];
    }
}
