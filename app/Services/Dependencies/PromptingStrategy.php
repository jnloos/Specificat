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
        // TODO: Implement getAssistantSummary() method.

        $params = [];
        // TODO: Prepare Data
        $prompt = view('prompts.assistant-summary', $params)->render();

        // TODO: Do Request with prompt output
        // TODO: Store Results
    }

    // Do request and save in the database
    abstract public function genExpertSummaries(): void;

    // Do request and save in the database
    abstract public function genNextMessage(): void;

    protected function needsExpertSummariesRefresh(): bool {
        $numMsg = $this->project->messages()->count();
        $freq = $this->project->summary_frequency;
        return $numMsg > 0 && $numMsg % $freq == 0;
    }
}
