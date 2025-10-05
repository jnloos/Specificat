<?php
namespace App\Services;

use App\Services\Dependencies\LLMClient;
use JsonException;
use Log;

class OpenAIClient implements LLMClient
{
    protected string $pythonPath;

    protected PythonVenv $pythonVenv;

    public function __construct() {
        $this->pythonVenv = new PythonVenv();
        $this->pythonPath = config('openai.python_path', 'python3');
    }

    public function updateExpertSummaries(array $project, array $experts): array {
        $payload = [
            'experts' => $experts,
            'project' => $project,
        ];

        $jsonInput = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $jsonOutput = $this->pythonVenv->exec('openai-clients/summary_gen.py', [$jsonInput]);

        /**
         * Input JSON → summary_gen.py
         * {
         *   "experts": [
         *     { "expert_id": 1, "name": "Alice", "job": "Backend Engineer", "description": "...", "thoughts": "..." },
         *     { "expert_id": 2, "name": "Bob", "job": "UX Designer", "description": "...", "thoughts": "..." }
         *   ],
         *   "project": {
         *     "title": "Project Title",
         *     "description": "Project Description",
         *     "messages": [
         *       { "expert_id": 1, "content": "Some message text" },
         *       { "expert_id": 2, "content": "Another message text" },
         *       { "expert_id": null, "content": "A users message text" }
         *     ]
         *   }
         * }
         *
         * Output JSON ← summary_gen.py
         * {
         *   "1": "Updated summary text for expert 1",
         *   "2": "Updated summary text for expert 2"
         * }
         */

        return json_decode($jsonOutput, associative: true);
    }

    public function getNextMessage(array $project, array $experts): array {
        $payload = [
            'experts' => $experts,
            'project' => $project
        ];

        $jsonInput = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Log::debug($jsonInput);

        $jsonOutput = $this->pythonVenv->exec('openai-clients/discussion_step.py', [$jsonInput]);

        /**
         * Input JSON → discussion_step.py
         * {
         *   "experts": [
         *     { "expert_id": 1, "name": "Alice", "job": "Backend Engineer", "description": "...", "thoughts": "..." },
         *     { "expert_id": 2, "name": "Bob", "job": "UX Designer", "description": "...", "thoughts": "..." }
         *   ],
         *   "project": {
         *     "title": "Project Title",
         *     "description": "Project Description",
         *     "messages": [
         *       { "expert_id": 1, "content": "Some message text" },
         *       { "expert_id": 2, "content": "Another message text" },
         *       { "expert_id": null, "content": "A users message text" }
         *     ]
         *   }
         * }
         *
         * Output JSON ← discussion_step.py
         * {
         *   "1": { "statement": "Expert 1’s contribution", "importance": 8 },
         *   "2": { "statement": "Expert 2’s contribution", "importance": 5 }
         * }
         */

        $cleanJson = $this->unwrapJson($jsonOutput);
        try {
            Log::debug($cleanJson);
            return json_decode($cleanJson, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $e) {
            Log::error('Invalid JSON from discussion_step.py', [
                'output' => $cleanJson,
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function unwrapJson(string $output): string {
        $trimmed = trim($output);

        // Entfernt alle ```json ... ``` oder ``` ... ```
        if (preg_match('/^```(?:json)?\s*(.*)```$/is', $trimmed, $m)) {
            return trim($m[1]);
        }

        return $trimmed;
    }

    public function getUserSummary(array $project, array $experts): array {
        // TODO
        return [];
    }
}
