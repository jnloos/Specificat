<?php

namespace App\Services\OpenAI;

use App\Models\Project;
use App\Services\OpenAI\Helpers\ChatGenerator;
use App\Services\OpenAI\Helpers\SummaryGenerator;

class Intelligence
{
    public function generateMessage(Project $project): void {
        $gen = new ChatGenerator($project);
        $gen->generate();

        if ($this->needsSummaryRefresh($project)) {
            $this->refreshSummaries($project);
        }
    }

    public function refreshSummaries(Project $project): void {
        $summary = new SummaryGenerator($project);
        $summary->generate();
    }

    private function needsSummaryRefresh(Project $project): bool {
        $numMsg = $project->messages()->count();
        $freq   = $project->summary_frequency;
        return $numMsg > 0 && $numMsg % $freq === 0;
    }
}
