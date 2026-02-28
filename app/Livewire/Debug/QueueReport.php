<?php

namespace App\Livewire\Debug;

use App\Events\Debug\Queue\JobDispatched;
use App\Events\Debug\Queue\JobFailed;
use App\Events\Debug\Queue\JobProcessing;
use App\Events\Debug\Queue\JobSucceeded;
use Livewire\Attributes\On;
use Livewire\Component;

class QueueReport extends Component
{
    /** @var array<string, array<string, mixed>> Keyed by jid */
    public array $jobs = [];

    private const MAX_JOBS = 100;

    #[On('native:' . JobDispatched::class)]
    public function onJobDispatched(
        string $jid,
        string $class,
        string $time,
        string $queue = 'default',
        array $payload = [],
    ): void {
        $this->jobs[$jid] = [
            'id'            => $jid,
            'class'         => class_basename($class),
            'fullClass'     => $class,
            'status'        => 'dispatched',
            'queue'         => $queue,
            'dispatched_at' => $time,
            'started_at'    => null,
            'finished_at'   => null,
            'duration_ms'   => null,
            'error'         => null,
            'trace'         => null,
            'expanded'      => false,
        ];

        $this->trimJobs();
        $this->dispatchCount();
    }

    #[On('native:' . JobProcessing::class)]
    public function onJobProcessing(
        string $jid,
        string $class,
        string $time,
        string $queue = 'default',
    ): void {
        if (isset($this->jobs[$jid])) {
            $this->jobs[$jid]['status']     = 'running';
            $this->jobs[$jid]['queue']      = $queue;
            $this->jobs[$jid]['started_at'] = $time;
        } else {
            $this->jobs[$jid] = [
                'id'            => $jid,
                'class'         => class_basename($class),
                'fullClass'     => $class,
                'status'        => 'running',
                'queue'         => $queue,
                'dispatched_at' => null,
                'started_at'    => $time,
                'finished_at'   => null,
                'duration_ms'   => null,
                'error'         => null,
                'trace'         => null,
                'expanded'      => false,
            ];

            $this->trimJobs();
            $this->dispatchCount();
        }
    }

    #[On('native:' . JobSucceeded::class)]
    public function onJobSucceeded(
        string $jid,
        string $class,
        string $time,
        string $queue = 'default',
    ): void {
        if (isset($this->jobs[$jid])) {
            $this->jobs[$jid]['status']      = 'success';
            $this->jobs[$jid]['queue']       = $queue;
            $this->jobs[$jid]['finished_at'] = $time;
            $this->jobs[$jid]['duration_ms'] = $this->calculateDurationMs(
                $this->jobs[$jid]['started_at'],
                $time,
            );
        }
    }

    #[On('native:' . JobFailed::class)]
    public function onJobFailed(
        string $jid,
        string $class,
        string $time,
        string $queue = 'default',
        string $error = '',
        string $trace = '',
    ): void {
        if (isset($this->jobs[$jid])) {
            $this->jobs[$jid]['status']      = 'failed';
            $this->jobs[$jid]['queue']       = $queue;
            $this->jobs[$jid]['finished_at'] = $time;
            $this->jobs[$jid]['duration_ms'] = $this->calculateDurationMs(
                $this->jobs[$jid]['started_at'],
                $time,
            );
            $this->jobs[$jid]['error'] = $error;
            $this->jobs[$jid]['trace'] = $trace;
        } else {
            $this->jobs[$jid] = [
                'id'            => $jid,
                'class'         => class_basename($class),
                'fullClass'     => $class,
                'status'        => 'failed',
                'queue'         => $queue,
                'dispatched_at' => null,
                'started_at'    => null,
                'finished_at'   => $time,
                'duration_ms'   => null,
                'error'         => $error,
                'trace'         => $trace,
                'expanded'      => false,
            ];

            $this->trimJobs();
            $this->dispatchCount();
        }
    }

    public function toggleExpand(string $jid): void
    {
        if (isset($this->jobs[$jid])) {
            $this->jobs[$jid]['expanded'] = ! $this->jobs[$jid]['expanded'];
        }
    }

    #[On('clear-jobs')]
    public function clearJobs(): void
    {
        $this->jobs = [];
        $this->dispatchCount();
    }

    public function render(): mixed
    {
        return view('livewire.debug.queue-report');
    }

    private function dispatchCount(): void
    {
        $this->dispatch('jobs-count-updated', count: count($this->jobs));
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

        return (strtotime($finishedAt) - strtotime($startedAt)) * 1000;
    }
}