<?php
namespace App\Services;

use App\Models\Expert;
use App\Models\Project;
use App\Services\Dependencies\PromptingStrategy;

class SinglePrompting extends PromptingStrategy
{
    public function genExpertSummaries(): void {
        // Prepare Data
        $params = [];
        $this['project'] = $this->project->asPromptArray();
        $params['experts'] = $this->project->contributingExperts->asPromptArray();
        $prompt = view('prompts.single.expert-summaries', $params)->render();

        // Send Request
        $response = json_decode($this->sendPrompt($prompt));
        if (empty($response)) {
            return;
        }

        // Store fresh summaries for each expert
        foreach ($response as $expertId => $newSummary) {
            $expert = Expert::find($expertId);
            if (!$expert) {
                continue;
            }

            $summary = $expert->thoughtsAbout($this->project);
            $summary->content = $newSummary;
            $summary->save();
        }
    }

    public function genNextMessage(): void {
        // Prepare Data
        $params = [];
        $this['project'] = $this->project->asPromptArray();
        $params['experts'] = $this->project->contributingExperts->asPromptArray();
        $prompt = view('prompts.single.next-message', $params)->render();

        // Send Request
        $response = json_decode($this->sendPrompt($prompt));
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

        if($this->needsExpertSummariesRefresh()) {
            $this->genExpertSummaries();
        }

        // Store thoughts for each expert
        $experts = $this->project->contributingExperts();
        foreach ($experts as $expert) {
            $thought = $expert->thoughtsAbout($this->project);

            if ($expert->id === $importantExpert && isset($importantContent['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " was able to contribute: " . $importantContent['statement'];
            }
            elseif (isset($response[$expert->id]['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " wanted to contribute: " . $response[$expert->id]['statement'];
            }

            $thought->save();
        }
    }
}
