<?php

namespace App\Livewire\Debug;

use Livewire\Attributes\On;
use Livewire\Component;

class DebugMonitor extends Component
{
    public string $activeTab = 'jobs';
    public int $jobCount     = 0;
    public int $promptCount  = 0;

    public function clearActive(): void
    {
        if ($this->activeTab === 'jobs') {
            $this->dispatch('clear-jobs')->to(QueueReport::class);
        } else {
            $this->dispatch('clear-prompts')->to(PromptReport::class);
        }
    }

    #[On('jobs-count-updated')]
    public function updateJobCount(int $count): void
    {
        $this->jobCount = $count;
    }

    #[On('prompts-count-updated')]
    public function updatePromptCount(int $count): void
    {
        $this->promptCount = $count;
    }

    public function render(): mixed
    {
        return view('livewire.debug.debug-monitor');
    }
}
