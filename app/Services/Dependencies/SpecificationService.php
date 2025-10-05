<?php
namespace App\Services\Dependencies;

abstract class SpecificationService extends LLMService
{
    use HasProject;

    public abstract function generateNextMessage(): void;

    protected function needsExpertSummary(): bool {
        $numMsg = $this->project->messages()->count();
        $freq = $this->project->summary_frequency;
        return $numMsg > 0 && $numMsg % $freq == 0;
    }
}
