<?php
namespace App\Services\Dependencies;

use App\Models\Project;

trait HasProject
{
    protected Project $project;

    public function __construct(Project $project) {
        $this->project = $project;
    }

    public static function forProject(Project $project): static {
        return new static($project);
    }
}
