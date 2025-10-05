<?php
namespace App\Jobs;

use App\Facades\Summary;
use App\Jobs\Dependencies\ProjectJob;
use App\Models\Project;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class SummaryGenerator extends ProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(int $projectId) {
        $this->setProject($projectId);
    }

    public function handle(): void {
        $this->withProjectLock(function (Project $project) {
            try {
                Summary::forProject($project)->generateUserSummary();
            }
            catch (Exception $e) {
                Log::error(sprintf(
                    "%s: %s",
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));
            }
        });
    }
}
