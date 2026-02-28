<?php
namespace App\Livewire\Projects;

use App\Events\MessageGenerated;
use App\Jobs\GenerateNextMsg;
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

    public bool $keepGenerating = false;
    public bool $isDispatching = false;

    #[Validate('required|string|min:3|max:1000')]
    public string $msgContent = '';

    public function mount(Project $project): void {
        $this->project   = $project;
        $this->projectId = $project->id;
    }

    public function hydrate(): void {
        $this->project = Project::findOrFail($this->projectId);
    }

    public function startGenerate(): void {
        if ($this->isDispatching) {
            return;
        }

        $this->keepGenerating = true;
        $this->isDispatching  = true;
        GenerateNextMsg::dispatch($this->projectId);
    }

    public function stopGenerate(): void {
        $this->keepGenerating = false;
    }

    #[On('native:' . MessageGenerated::class)]
    public function onMessageGenerated(int $projectId): void {
        if ($projectId !== $this->projectId) {
            return;
        }

        $this->dispatch('message_sent');

        if ($this->keepGenerating) {
            GenerateNextMsg::dispatch($this->projectId);
        } else {
            $this->isDispatching = false;
        }
    }

    public function sendMessage(): void {
        if ($this->isDispatching) {
            return;
        }

        $this->validate();
        $this->project->addMessage($this->msgContent, null);
        $this->dispatch('message_sent');
        $this->reset('msgContent');
    }

    #[On('contributors_modified')]
    public function render(): mixed {
        return view('livewire.projects.control-chat', [
            'disableAll' => $this->isDispatching,
            'showGenerate' => ! $this->keepGenerating,
            'showStop' => $this->keepGenerating,
        ]);
    }
}
