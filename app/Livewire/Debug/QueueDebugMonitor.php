<?php

namespace App\Livewire\Debug;

use App\Events\Queue\JobDispatched;
use App\Events\Queue\JobFailed;
use App\Events\Queue\JobProcessing;
use App\Events\Queue\JobSucceeded;
use Livewire\Attributes\On;
use Livewire\Component;

class QueueDebugMonitor extends Component
{
    /** @var array<string, array<string, mixed>> Keyed by jobId */
    public array $jobs = [];

    private const MAX_JOBS = 100;

    #[On('native:' . JobDispatched::class)]
    public function onJobDispatched(
        string $jobId,
        string $jobClass,
        string $timestamp,
        string $queue = 'default',
        array $payload = [],
    ): void {
        $this->jobs[$jobId] = [
            'id'            => $jobId,
            'class'         => class_basename($jobClass),
            'fullClass'     => $jobClass,
            'status'        => 'dispatched',
            'queue'         => $queue,
            'dispatched_at' => $timestamp,
            'started_at'    => null,
            'finished_at'   => null,
            'duration_ms'   => null,
            'error'         => null,
            'trace'         => null,
            'expanded'      => false,
        ];

        $this->trimJobs();
    }

    #[On('native:' . JobProcessing::class)]
    public function onJobProcessing(
        string $jobId,
        string $jobClass,
        string $timestamp,
        string $queue = 'default',
    ): void {
        if (isset($this->jobs[$jobId])) {
            $this->jobs[$jobId]['status']     = 'running';
            $this->jobs[$jobId]['queue']      = $queue;
            $this->jobs[$jobId]['started_at'] = $timestamp;
        } else {
            // Worker picked up a job that was dispatched before the debug window opened
            $this->jobs[$jobId] = [
                'id'            => $jobId,
                'class'         => class_basename($jobClass),
                'fullClass'     => $jobClass,
                'status'        => 'running',
                'queue'         => $queue,
                'dispatched_at' => null,
                'started_at'    => $timestamp,
                'finished_at'   => null,
                'duration_ms'   => null,
                'error'         => null,
                'trace'         => null,
                'expanded'      => false,
            ];

            $this->trimJobs();
        }
    }

    #[On('native:' . JobSucceeded::class)]
    public function onJobSucceeded(
        string $jobId,
        string $jobClass,
        string $timestamp,
        string $queue = 'default',
    ): void {
        if (isset($this->jobs[$jobId])) {
            $this->jobs[$jobId]['status']      = 'success';
            $this->jobs[$jobId]['queue']       = $queue;
            $this->jobs[$jobId]['finished_at'] = $timestamp;
            $this->jobs[$jobId]['duration_ms'] = $this->calculateDurationMs(
                $this->jobs[$jobId]['started_at'],
                $timestamp,
            );
        }
    }

    #[On('native:' . JobFailed::class)]
    public function onJobFailed(
        string $jobId,
        string $jobClass,
        string $timestamp,
        string $queue = 'default',
        string $errorMessage = '',
        string $stackTrace = '',
    ): void {
        if (isset($this->jobs[$jobId])) {
            $this->jobs[$jobId]['status']      = 'failed';
            $this->jobs[$jobId]['queue']       = $queue;
            $this->jobs[$jobId]['finished_at'] = $timestamp;
            $this->jobs[$jobId]['duration_ms'] = $this->calculateDurationMs(
                $this->jobs[$jobId]['started_at'],
                $timestamp,
            );
            $this->jobs[$jobId]['error'] = $errorMessage;
            $this->jobs[$jobId]['trace'] = $stackTrace;
        } else {
            // Failed before we saw the dispatch (edge case)
            $this->jobs[$jobId] = [
                'id'            => $jobId,
                'class'         => class_basename($jobClass),
                'fullClass'     => $jobClass,
                'status'        => 'failed',
                'queue'         => $queue,
                'dispatched_at' => null,
                'started_at'    => null,
                'finished_at'   => $timestamp,
                'duration_ms'   => null,
                'error'         => $errorMessage,
                'trace'         => $stackTrace,
                'expanded'      => false,
            ];

            $this->trimJobs();
        }
    }

    public function toggleExpand(string $jobId): void
    {
        if (isset($this->jobs[$jobId])) {
            $this->jobs[$jobId]['expanded'] = ! $this->jobs[$jobId]['expanded'];
        }
    }

    public function clearJobs(): void
    {
        $this->jobs = [];
    }

    public function render(): mixed
    {
        return view('livewire.debug.queue-debug-monitor');
    }

    private function trimJobs(): void
    {
        if (count($this->jobs) > self::MAX_JOBS) {
            $this->jobs = array_slice($this->jobs, -self::MAX_JOBS, self::MAX_JOBS, preserve_keys: true);
        }
    }

    private function calculateDurationMs(?string $startedAt, string $finishedAt): ?int
    {
        if ($startedAt === null) {
            return null;
        }

        $start = strtotime($startedAt);
        $end   = strtotime($finishedAt);

        return ($end - $start) * 1000;
    }
}