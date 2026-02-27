<?php

namespace App\Services\OpenAI;

use App\Services\Deps\Notetaker as NotetakerContract;
use Illuminate\Support\Collection;
use OpenAI\Client;
use Throwable;

class Notetaker implements NotetakerContract
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function improveSegments(Collection $segments, ?string $locale = null): array
    {
        if ($segments->isEmpty()) {
            return [];
        }

        $segmentsData = $segments->map(static function ($segment): array {
            return [
                'id' => (int) $segment->id,
                'text' => (string) $segment->text,
            ];
        })->all();

        $payload = json_encode($segmentsData, JSON_PRETTY_PRINT);

        $prompt = trim(view('prompts.improve-transcription', [
            'locale' => $locale,
            'segments' => $payload,
        ])->render());

        $response = $this->client->responses()->create([
            'model' => 'gpt-4o-mini',
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                    ],
                ],
            ],
        ]);

        $output = trim((string) ($response->outputText ?? ''));
        if ($output === '') {
            return $segmentsData;
        }

        // Extract JSON from markdown code blocks if present
        $output = preg_replace('/^```json\s*\n/m', '', $output);
        $output = preg_replace('/\n```$/m', '', $output);
        $output = trim($output);

        $decoded = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $segmentsData;
        }

        return $decoded;
    }
}
