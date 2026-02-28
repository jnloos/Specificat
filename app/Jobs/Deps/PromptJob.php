<?php

namespace App\Jobs\Deps;

use App\Events\Debug\Prompts\PromptFail;
use App\Events\Debug\Prompts\PromptRequest;
use App\Events\Debug\Prompts\PromptSuccess;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

abstract class PromptJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;
    protected string $cacheKey;

    protected string $model = '';
    protected string $input = '';
    protected array $attachments = [];

    public function __construct() {
        $this->cacheKey = (string) Str::uuid();
    }

    abstract protected function prompt(): string;

    final public function output(): string {
        return Cache::pull($this->cacheKey);
    }

    final public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $pid = (string) Str::uuid();
        $model = $this->model();
        $input = $this->input();
        $attachments = $this->attachments();
        event(new PromptRequest($pid, now()->toIso8601String(), $model, $input, $attachments));

        try {
            $response = $this->prompt();
            Cache::put($this->cacheKey, $response, now()->addMinutes(10));
            event(new PromptSuccess($pid, now()->toIso8601String(), $response));
        }
        catch (Throwable $e) {
            $msg = $e->getMessage();
            $trace = $e->getTraceAsString();
            event(new PromptFail($pid, now()->toIso8601String(), $msg, $trace));

            throw $e;
        }
    }

    protected function model(): string {
        return $this->model;
    }

    protected function input(): string {
        return $this->input;
    }

    protected function attachments(): array {
        return $this->attachments;
    }
}
