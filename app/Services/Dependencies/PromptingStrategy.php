<?php
namespace App\Services\Dependencies;

use App\Models\Project;

abstract class PromptingStrategy
{
    use HasProject;

    public static function lockName(int $projectId): string {
        return "project_{$projectId}_lock";
    }

    // Do request and save in the database
    public function genAssistantSummary(): void {
        // TODO
    }

    // Do request and save in the database
    abstract public function genExpertSummaries(): void;

    // Do request and save in the database
    abstract public function genNextMessage(): void;

    protected function needsExpertSummary(): bool {
        $numMsg = $this->project->messages()->count();
        $freq = $this->project->summary_frequency;
        return $numMsg > 0 && $numMsg % $freq == 0;
    }
}
