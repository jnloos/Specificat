<?php

namespace App\Providers;

use App\Events\Debug\Queue\JobDispatched as DebugJobDispatched;
use App\Events\Debug\Queue\JobFailed as DebugJobFailed;
use App\Events\Debug\Queue\JobProcessing as DebugJobProcessing;
use App\Events\Debug\Queue\JobSucceeded as DebugJobSucceeded;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! config('app.debug') && ! app()->environment('local')) {
            return;
        }

        $events = app('events');

        // Fire when a job is pushed onto the queue
        $events->listen(JobQueued::class, function (JobQueued $event) {
            $jobId    = (string) $event->id;
            $jobClass = is_object($event->job) ? get_class($event->job) : (string) $event->job;
            $queue    = $event->queue ?? 'default';

            DebugJobDispatched::dispatch($jobId, $jobClass, now()->toIso8601String(), $queue);
        });

        // Fire when the queue worker picks up a job
        $events->listen(JobProcessing::class, function (JobProcessing $event) {
            $jobId    = (string) $event->job->getJobId();
            $payload  = $event->job->payload();
            $jobClass = $payload['displayName'] ?? $event->job->getName();
            $queue    = $event->job->getQueue();

            DebugJobProcessing::dispatch($jobId, $jobClass, now()->toIso8601String(), $queue);
        });

        // Fire when a job completes successfully
        $events->listen(JobProcessed::class, function (JobProcessed $event) {
            $jobId    = (string) $event->job->getJobId();
            $payload  = $event->job->payload();
            $jobClass = $payload['displayName'] ?? $event->job->getName();
            $queue    = $event->job->getQueue();

            DebugJobSucceeded::dispatch($jobId, $jobClass, now()->toIso8601String(), $queue);
        });

        // Fire when a job fails
        $events->listen(JobFailed::class, function (JobFailed $event) {
            $jobId    = (string) $event->job->getJobId();
            $payload  = $event->job->payload();
            $jobClass = $payload['displayName'] ?? $event->job->getName();
            $queue    = $event->job->getQueue();

            $errorMessage = $event->exception->getMessage();
            $stackTrace   = $event->exception->getTraceAsString();

            DebugJobFailed::dispatch($jobId, $jobClass, now()->toIso8601String(), $queue, $errorMessage, $stackTrace);
        });
    }
}
