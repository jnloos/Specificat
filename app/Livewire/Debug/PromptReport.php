<?php

namespace App\Livewire\Debug;

use App\Events\Debug\Prompts\PromptFail;
use App\Events\Debug\Prompts\PromptRequest;
use App\Events\Debug\Prompts\PromptSuccess;
use Livewire\Attributes\On;
use Livewire\Component;

class PromptReport extends Component
{
    /** @var array<string, array<string, mixed>> Keyed by pid */
    public array $prompts = [];

    private const MAX_PROMPTS = 100;

    #[On('native:' . PromptRequest::class)]
    public function onPromptRequest(
        string $pid,
        string $time,
        string $model = '',
        string $input = '',
        array $attachments = [],
    ): void {
        $this->prompts[$pid] = [
            'id'           => $pid,
            'status'       => 'pending',
            'requested_at' => $time,
            'completed_at' => null,
            'duration_ms'  => null,
            'model'        => $model,
            'input'        => $input,
            'attachments'  => $attachments,
            'response'     => null,
            'inputTokens'  => null,
            'outputTokens' => null,
            'error'        => null,
            'trace'        => null,
            'expanded'     => false,
        ];

        $this->trimPrompts();
        $this->dispatchCount();
    }

    #[On('native:' . PromptSuccess::class)]
    public function onPromptSuccess(
        string $pid,
        string $time,
        string $response,
        int $inputTokens = 0,
        int $outputTokens = 0,
    ): void {
        if (isset($this->prompts[$pid])) {
            $this->prompts[$pid]['status']       = 'success';
            $this->prompts[$pid]['completed_at'] = $time;
            $this->prompts[$pid]['duration_ms']  = $this->calculateDurationMs(
                $this->prompts[$pid]['requested_at'],
                $time,
            );
            $this->prompts[$pid]['response']     = $response;
            $this->prompts[$pid]['inputTokens']  = $inputTokens;
            $this->prompts[$pid]['outputTokens'] = $outputTokens;
        }
    }

    #[On('native:' . PromptFail::class)]
    public function onPromptFail(
        string $pid,
        string $time,
        string $msg = '',
        string $trace = '',
    ): void {
        if (isset($this->prompts[$pid])) {
            $this->prompts[$pid]['status']       = 'failed';
            $this->prompts[$pid]['completed_at'] = $time;
            $this->prompts[$pid]['duration_ms']  = $this->calculateDurationMs(
                $this->prompts[$pid]['requested_at'],
                $time,
            );
            $this->prompts[$pid]['error'] = $msg;
            $this->prompts[$pid]['trace'] = $trace;
        }
    }

    public function togglePromptExpand(string $pid): void
    {
        if (isset($this->prompts[$pid])) {
            $this->prompts[$pid]['expanded'] = ! $this->prompts[$pid]['expanded'];
        }
    }

    #[On('clear-prompts')]
    public function clearPrompts(): void
    {
        $this->prompts = [];
        $this->dispatchCount();
    }

    public function render(): mixed
    {
        return view('livewire.debug.prompt-report');
    }

    private function dispatchCount(): void
    {
        $this->dispatch('prompts-count-updated', count: count($this->prompts));
    }

    private function trimPrompts(): void
    {
        if (count($this->prompts) > self::MAX_PROMPTS) {
            $this->prompts = array_slice($this->prompts, -self::MAX_PROMPTS, self::MAX_PROMPTS, preserve_keys: true);
        }
    }

    private function calculateDurationMs(?string $startedAt, string $finishedAt): ?int
    {
        if ($startedAt === null) {
            return null;
        }

        return (strtotime($finishedAt) - strtotime($startedAt)) * 1000;
    }
}