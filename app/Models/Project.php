<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Project extends Model
{
    public function messages(): HasMany {
        return $this->hasMany(Message::class);
    }

    public function summaries(): HasMany {
        return $this->hasMany(Summary::class);
    }

    public function experts(): BelongsToMany {
        return $this->belongsToMany(Expert::class, 'expert_project');
    }

    public function isPersistent(): bool {
        return $this->experts()->count() > 0;
    }

    public function addContributingExpert(Expert $expert): void {
        $this->experts()->syncWithoutDetaching($expert->id);
    }

    public function removeContributingExpert(Expert $expert): void {
        $this->experts()->detach($expert->id);
    }

    public function contributingExperts(): Collection {
        return $this->experts()->get();
    }

    protected static function booted(): void {
        static::created(function (Project $project): void {
            $welcomeMsg = view('components.projects.welcome-message', [
                'project' => $project
            ])->render();
            $project->addMessage($welcomeMsg, null);
        });
    }

    public function addMessage(string $content, ?Expert $expert): Message {
        $message = new Message();
        $message->project_id = $this->id;
        $message->content = $content;
        $message->expert_id = $expert?->id;
        $message->save();
        return $message;
    }

    public function asPromptArray(int $numMsg = -1): array {
        $query = $this->messages()->latest();
        if ($numMsg > -1) {
            $query->take($numMsg);
        }

        $messages = $query->get()->map(fn (Message $msg) => $msg->toPromptArray())->values()->all();
        return [
            'title' => $this->title,
            'description' => $this->description,
            'messages' => $messages,
        ];
    }
}