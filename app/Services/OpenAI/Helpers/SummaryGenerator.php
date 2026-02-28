<?php

namespace App\Services\OpenAI\Helpers;

use App\Jobs\OpenAI\SimpleResponse;
use App\Models\Project;
use Illuminate\Support\Facades\Bus;

class SummaryGenerator
{
    private string $model;
    protected Project $project;

    public function __construct(Project $project) {
        $this->model = config('apis.openai.model');
        $this->project = $project;
    }

    public function generate(): void
    {
        $prompts = $this->prepare();
        if (empty($prompts)) {
            return;
        }

        $responses = array_combine(array_keys($prompts), $this->sendPrompts(array_values($prompts)));
        $this->refurbish($responses);
    }

    protected function prepare(): array
    {
        $prompts = [];
        $experts = $this->project->experts;

        foreach ($experts as $expert) {
            $params = [
                'project' => $this->project->asPromptArray(),
                'expert'  => $expert->asPromptArray($this->project),
            ];
            $prompts[$expert->id] = view('prompts.expert-summaries', $params)->render();
        }

        return $prompts;
    }

    protected function dispatch(array $prompts): array
    {
        return array_combine(
            array_keys($prompts),
            $this->sendPrompts(array_values($prompts))
        );
    }

    protected function refurbish(array $responses): void
    {
        foreach ($responses as $expertId => $json) {
            $response = json_decode($json, true);
            if (empty($response)) {
                continue;
            }

            $expert = $this->project->experts()->find($expertId);
            if (!$expert) {
                continue;
            }

            $summary = $expert->thoughtsAbout($this->project);
            $summary->content = $response;
            $summary->save();
        }
    }

    private function sendPrompts(array $prompts): array
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
