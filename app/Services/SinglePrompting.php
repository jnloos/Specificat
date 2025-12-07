<?php
namespace App\Services;

use App\Models\Project;
use App\Services\Dependencies\PromptingStrategy;

class SinglePrompting extends PromptingStrategy
{
    public function genExpertSummaries(): void {
        // TODO: Implement updateExpertSummaries() method.

        $params = [];
        // TODO: Prepare Data
        $prompt = view('prompts.single.expert-summaries', $params)->render();

        // TODO: Do Request with prompt output
        // TODO: Store Results
    }

    public function genNextMessage(): void {
        // TODO: Implement getNextMessage() method.

        $params = [];
        // TODO: Prepare Data
        $prompt = view('prompts.single.next-message', $params)->render();

        // TODO: Do Request with prompt output
        // TODO: Store Results

        if($this->needsExpertSummariesRefresh()) {
            $this->genExpertSummaries();
        }
    }
}
