<?php

namespace App\Services\Dependencies;

interface LLMClient
{
    public function updateExpertSummaries(array $project, array $experts): array;
    public function getUserSummary(array $project, array $experts): array;

    public function getNextMessage(array $project, array $experts): array;
}
