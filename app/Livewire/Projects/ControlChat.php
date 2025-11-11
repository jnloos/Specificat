<?php
namespace App\Livewire\Projects;

use App\Facades\Summary;
use App\Jobs\Dependencies\ProjectJob;
use App\Jobs\MessageGenerator;
use App\Models\Project;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ControlChat extends Component
{
    #[Locked]
    public int $projectId;
    protected Project $project;

    #[Validate('required|string|min:3|max:1000')]
    public string $msgContent = '';

    public function mount(Project $project): void {
        $project->setPaused();
        $this->projectId = $project->id;
        $this->project = $project;
    }

    public function hydrate(): void {
        $this->project = Project::with('contributors')->findOrFail($this->projectId);
    }

    public function generateMessages(): void {
        // Switch from pause to generate or vice versa
        if ($this->project->shouldPause()) {
            $this->project->setGenerating();
        }
        else {
            $this->project->setPaused();
        }
    }

    public function generateSummary(): void {
        $this->project->setSummarizing();
        Summary::forProject($this->project)->generateUserSummary();
        $this->project->setPaused();
        $this->tick();
    }

    public function tick(): void {
        if ($this->project->shouldGenerate()) {
            if(! ProjectJob::isRunningFor($this->projectId)) {
                MessageGenerator::dispatch($this->projectId);
            }
        }

        $this->dispatch('ticked');
    }

    public function sendMessage(): void {
        $this->validate();

        $this->project->addMessage($this->msgContent, contributor: auth()->user());

        $this->dispatch('message_sent');
        $this->reset('msgContent');
    }

    #[On(['contributors_modified'])]
    public function render(): mixed {
        $persistent = $this->project->isPersistent();

        $shouldRun = $this->project->shouldGenerate() || $this->project->shouldSummarize();
        $awaitsMessage = ProjectJob::isRunningFor($this->project->id) || $shouldRun;

        if($this->project->shouldGenerate()) {
            $iconGenerate = 'pause';
        }
        else {
            $iconGenerate = 'play';
        }


        return view('livewire.projects.control-chat', [
            'awaitsMessage' => $awaitsMessage,
            'disableSummary' => $awaitsMessage,
            'disableMessage' => !$persistent || $awaitsMessage,
            'disableGenerate' => !$persistent || ($this->project->shouldPause() && $awaitsMessage),
            'iconGenerate' => $iconGenerate
        ]);
    }
}
