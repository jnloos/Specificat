<?php
namespace App\Services;

use App\Models\Expert;
use App\Services\Dependencies\LLMClient;
use App\Services\Dependencies\SpecificationService;

class UnifiedPrompting extends SpecificationService
{
    public function generateNextMessage(): void {
        $response = $this->sendPrompt();
        $this->processResponse($response);
    }

    protected function sendPrompt(): array {
        $projectData = $this->project->asPromptArray(numMsg: 12);

        $expertData = [];
        foreach ($this->project->contributingExperts() as $expert) {
            $expertData[] = $expert->asPromptArray($this->project);
        }

        return $this->aiClient->getNextMessage($projectData, $expertData);
    }

    protected function processResponse(array $response): void {
        if (empty($response)) {
            return;
        }

        // Sort by importance
        uasort($response, function ($a, $b) {
            return ($b['importance'] ?? 0) <=> ($a['importance'] ?? 0);
        });

        // Determine the most important message and store it
        $importantExpert  = array_key_first($response);
        $importantContent = reset($response);

        $expert = Expert::find($importantExpert);
        if ($expert && isset($importantContent['statement'])) {
            $this->project->addMessage($importantContent['statement'], contributor: $expert);
        }

        // If required create new LLM summaries
        if ($this->needsExpertSummary()) {
            $projectData = $this->project->asPromptArray(numMsg: 12);

            $expertData = [];
            foreach ($this->project->contributingExperts() as $expert) {
                $expertData[] = $expert->asPromptArray($this->project);
            }

            $summaries = $this->aiClient->updateExpertSummaries($projectData, $expertData);
            foreach ($summaries as $expertId => $newSummary) {
                $expert = Expert::find($expertId);
                if (!$expert) {
                    continue;
                }
                $currSummary = $expert->thoughtsAbout($this->project);
                $currSummary->content = $newSummary;
                $currSummary->save();
            }
        }

        // Store thoughts for each expert
        $experts = $this->project->contributingExperts();
        foreach ($experts as $expert) {
            $thought = $expert->thoughtsAbout($this->project);

            if ($expert->id === $importantExpert && isset($importantContent['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " was able to contribute: " . $importantContent['statement'];
            } elseif (isset($response[$expert->id]['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " wanted to contribute: " . $response[$expert->id]['statement'];
            }

            $thought->save();
        }
    }
}
