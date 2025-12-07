<?php
namespace App\Services;

use App\Models\Contributor;
use App\Services\Dependencies\HasProject;

class AssistantSummary
{
    use HasProject;

    public function generateUserSummary(): void {
        // TODO: Testing code (To be removed!)

        $assistant = Contributor::assistant();
        $this->project->addMessage('Summary job is done.', contributor: $assistant);

        // $response = $this->sendPrompt();
        // $this->processResponse($response);
    }

    protected function sendPrompt(): array {
        $projectData = $this->project->asPromptArray(numMsg: 32);

        $expertData = [];
        foreach ($this->project->contributingExperts() as $expert) {
            $expertData[] = $expert->asPromptArray($this->project);
        }

        return $this->aiClient->getUserSummary($projectData, $expertData);
    }

    protected function processResponse(array $response): void {
        if (empty($response)) {
            return;
        }

        $assistant = Contributor::assistant();
        $this->project->addMessage($response['summary'], contributor: $assistant);
    }
}
