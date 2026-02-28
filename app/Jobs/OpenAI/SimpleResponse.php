<?php

namespace App\Jobs\OpenAI;

use App\Jobs\Deps\PromptJob;
use App\Services\OpenAI\AuthKey;
use Illuminate\Support\Facades\Cache;
use OpenAI;

class SimpleResponse extends PromptJob
{
    public function __construct(string $model, string $input) {
        parent::__construct();
        $this->model = $model;
        $this->input = $input;
    }

    protected function prompt(): string {
        $client = OpenAI::client(AuthKey::get());

        $result = $client->responses()->create([
            'model' => $this->model,
            'input' => $this->input,
        ])->outputText;

        return $result;
    }
}
