<?php
namespace App\Jobs;

use App\Events\MessageGenerated;
use App\Jobs\Deps\LockedOnProject;
use App\Jobs\Deps\ToastsExceptions;
use App\Models\Project;
use App\Services\OpenAI\ChatService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class MessageGenerator extends LockedOnProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ToastsExceptions;

    public int $timeout = 120;
    public int $tries  = 1;

    public function __construct(int $projectId) {
        $this->setProject($projectId);
    }

    public function handle(ChatService $chat): void {
        $this->withProjectLock(function (Project $project) use ($chat) {
            $chat->genNextMessage($project);
        });

        event(new MessageGenerated($this->project->id));
    }
}
