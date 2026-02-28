<?php

namespace App\Events\Debug\Queue;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobDispatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $jid,
        public string $class,
        public string $time,
        public string $queue = 'default',
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('nativephp'),
        ];
    }
}
