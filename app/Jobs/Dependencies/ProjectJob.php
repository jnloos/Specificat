<?php
namespace App\Jobs\Dependencies;

use App\Models\Project;
use App\Services\Dependencies\PromptingStrategy;
use Illuminate\Support\Facades\Cache;

class ProjectJob
{
    protected static int $lockTTL = 30;

    protected Project $project;

    public function setProject(int $projectId): void {
        $this->project = Project::findOrFail($projectId);
    }

    public static function isRunningFor(int $projectId): bool {
        $project = Project::findOrFail($projectId);
        $lock = Cache::lock(PromptingStrategy::lockName($project->id));
        $token = $lock->get();

        if ($token === false) {
            return true;
        }

        $lock->release();
        return false;
    }

    protected function withProjectLock(callable $callback): void {
        $lock = Cache::lock(PromptingStrategy::lockName($this->project->id), seconds: self::$lockTTL);
        $token = $lock->get();

        if ($token === false) {
            return;
        }

        try {
            $callback($this->project);
        } finally {
            $lock->release();
        }
    }
}
