<?php
namespace App\Services\Dependencies;

use App\Models\Project;

trait HasProject
{
    protected Project $project;

    public function forProject(Project $project): self {
        $this->project = $project;
        return $this;
    }
}
