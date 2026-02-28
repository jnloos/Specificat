<?php

namespace App\Services\OpenAI\Helpers;

use App\Jobs\OpenAI\SimpleResponse;
use App\Models\Expert;
use App\Models\Project;
use Illuminate\Support\Facades\Bus;

class ChatGenerator
{
    private string $model;
    protected Project $project;

    public function __construct(Project $project) {
        $this->model = config('apis.openai.model');
        $this->project = $project;
    }

    public function generate(): void {
        $prompts = $this->prepare();
        if (empty($prompts)) {
            return;
        }

        $responses = $this->sendPrompts($prompts);
        $this->refurbish($responses);
    }

    protected function prepare(): array
    {
        $prompts = [];
        $experts = $this->project->experts;

        foreach ($experts as $expert) {
            $params = [
                'project' => $this->project->asPromptArray(),
                'expert' => $expert->asPromptArray($this->project),
            ];
            $prompts[$expert->id] = view('prompts.next-message', $params)->render();
        }

        return $prompts;
    }

    protected function refurbish(array $responses): void
    {
        $messages = [];
        foreach ($responses as $response) {
            foreach ((array) json_decode($response, true) as $expertId => $data) {
                if (!empty($data)) {
                    $messages[$expertId] = $data;
                }
            }
        }

        if (empty($messages)) {
            return;
        }

        uasort($messages, fn($a, $b) => ($b['importance'] ?? 0) <=> ($a['importance'] ?? 0));

        $relevantExpert  = array_key_first($messages);
        $relevantMsg = $messages[$relevantExpert] ?? null;

        if ($relevantMsg && isset($relevantMsg['statement'])) {
            $expert = Expert::find($relevantExpert);
            if ($expert) {
                $this->project->addMessage($relevantMsg['statement'], $expert);
            }
        }

        $experts = $this->project->experts;
        foreach ($experts as $expert) {
            $thought = $expert->thoughtsAbout($this->project);

            if ($expert->id === $relevantExpert && isset($relevantMsg['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " was able to contribute: " . $relevantMsg['statement'];
            } elseif (isset($messages[$expert->id]['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " wanted to contribute: " . $messages[$expert->id]['statement'];
            }

            $thought->save();
        }
    }

    protected function sendPrompts(array $prompts): array
    {
        $jobs = array_map(function ($prompt) {
            return new SimpleResponse($this->model, $prompt);
        }, $prompts);

        $batch = Bus::batch($jobs)
            ->onQueue('openai')
            ->allowFailures()
            ->dispatch();

        // Block until every job in the batch has finished.
        $deadline = now()->addSeconds(110);
        do {
            usleep(300_000); // 300 ms
            $batch = $batch->fresh();
        } while (! $batch->finished() && now()->lt($deadline));

        return array_map(function ($job) {
            return $job->output();
        }, $jobs);
    }
}
