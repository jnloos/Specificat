<?php

namespace App\Services\OpenAI;

use App\Jobs\OpenAI\PromptJob;
use App\Models\Expert;
use App\Models\Project;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI;

class ChatService
{
    private string $model;

    public function __construct()
    {
        $this->model = config('apis.openai.model');
    }

    public function genNextMessage(Project $project): void
    {
        $experts = $project->contributingExperts();
        $prompts = [];

        foreach ($experts as $expert) {
            $params = [
                'project' => $project->asPromptArray(),
                'expert' => $expert->asPromptArray($project),
            ];
            $prompts[$expert->id] = view('prompts.next-message', $params)->render();
        }

        if (empty($prompts)) {
            return;
        }

        $responses = $this->sendPrompts($prompts);

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

        Log::info(json_encode($messages, JSON_PRETTY_PRINT));

        uasort($messages, fn($a, $b) => ($b['importance'] ?? 0) <=> ($a['importance'] ?? 0));

        $importantExpert  = array_key_first($messages);
        $importantContent = $messages[$importantExpert] ?? null;

        if ($importantContent && isset($importantContent['statement'])) {
            $expert = Expert::find($importantExpert);
            if ($expert) {
                $project->addMessage($importantContent['statement'], $expert);
            }
        }

        if ($this->needsSummaryRefresh($project)) {
            $this->genExpertSummaries($project);
        }

        foreach ($experts as $expert) {
            $thought = $expert->thoughtsAbout($project);

            if ($expert->id === $importantExpert && isset($importantContent['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " was able to contribute: " . $importantContent['statement'];
            } elseif (isset($messages[$expert->id]['statement'])) {
                $thought->content .= "\n\n" . $expert->name . " wanted to contribute: " . $messages[$expert->id]['statement'];
            }

            $thought->save();
        }
    }

    public function genExpertSummaries(Project $project): void
    {
        $experts = $project->contributingExperts();
        $prompts = [];

        foreach ($experts as $expert) {
            $params = [
                'project' => $project->asPromptArray(),
                'expert'  => $expert->asPromptArray($project),
            ];
            $prompts[$expert->id] = view('prompts.expert-summaries', $params)->render();
        }

        if (empty($prompts)) {
            return;
        }

        $responses = array_combine(
            array_keys($prompts),
            $this->sendPrompts(array_values($prompts))
        );

        foreach ($responses as $expertId => $json) {
            $response = json_decode($json, true);
            if (empty($response)) {
                continue;
            }

            $expert = Expert::find($expertId);
            if (!$expert) {
                continue;
            }

            $summary = $expert->thoughtsAbout($project);
            $summary->content = $response;
            $summary->save();
        }
    }

    private function sendPrompts(array $prompts): array
    {
        $batchId    = Str::uuid()->toString();
        $jobs       = [];
        $resultKeys = [];

        foreach ($prompts as $key => $prompt) {
            $resultKey          = "openai_prompt:$batchId:$key";
            $resultKeys[$key]   = $resultKey;
            $jobs[]             = new PromptJob($resultKey, $this->model, $prompt);
        }

        $batch = Bus::batch($jobs)
            ->onQueue('openai')
            ->allowFailures()
            ->dispatch();

        // Block until every job in the batch has finished (success or failure).
        // PromptJob runs on the dedicated 'openai' worker, so this worker
        // (running MessageGenerator on 'default') is free to poll without deadlock.
        $deadline = now()->addSeconds(110);
        do {
            usleep(300_000); // 300 ms
            $batch = $batch->fresh();
        } while (! $batch->finished() && now()->lt($deadline));

        $results = [];
        foreach ($resultKeys as $key => $resultKey) {
            $results[$key] = Cache::pull($resultKey) ?? '';
        }

        return $results;
    }

    private function needsSummaryRefresh(Project $project): bool
    {
        $numMsg = $project->messages()->count();
        $freq   = $project->summary_frequency;
        return $numMsg > 0 && $numMsg % $freq === 0;
    }
}
