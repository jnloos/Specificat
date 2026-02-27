<?php
namespace App\Jobs;

use App\Concerns\FiresToasts;
use App\Events\MessageGenerated;
use App\Jobs\Deps\ProjectJob;
use App\Jobs\Deps\ToastsExceptions;
use App\Models\Project;
use App\Services\Deps\PromptingStrategy;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class MessageGenerator extends ProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ToastsExceptions;

    public int $timeout = 120;

    public function __construct(int $projectId) {
        $this->setProject($projectId);
    }

    public function handle(): void {
        $this->withProjectLock(function (Project $project) {
            try {
                /** @var class-string<PromptingStrategy> $strategy */
                $strategy = $project->prompting_strategy::forProject($project);
                $strategy->genNextMessage();
            }
            catch (Exception $e) {
                $this->toastException($e);
            }
        });

        event(new MessageGenerated($this->project->id));
    }
}
