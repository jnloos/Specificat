<?php
namespace App\Jobs;

use App\Events\MessageGenerated;
use App\Models\Project;
use App\Services\OpenAI\Intelligence;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class GenerateNextMsg implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries  = 1;

    public function __construct(public int $projectId) {}

    public function uniqueId(): string
    {
        return "project:$this->projectId";
    }

    public function handle(Intelligence $chat): void {
        $project = Project::findOrFail($this->projectId);
        $chat->generateMessage($project);

        event(new MessageGenerated($this->projectId));
    }
}
