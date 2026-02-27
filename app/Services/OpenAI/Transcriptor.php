<?php

namespace App\Services\OpenAI;

use OpenAI\Client;

class Transcriptor implements \App\Services\Deps\Transcriptor
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return array<int, string>
     */
    public function transcribe(string $filePath, ?string $locale = null): array
    {
        $audio = fopen($filePath, 'r');

        try {
            $payload = [
                'model' => 'whisper-1',
                'file' => $audio,
                'response_format' => 'verbose_json',
                'timestamp_granularities' => ['segment', 'word'],
            ];

            if ($locale) {
                $payload['language'] = $locale;
            }

            $response = $this->client->audio()->transcribe($payload);
        } finally {
            if (is_resource($audio)) {
                fclose($audio);
            }
        }

        $segments = [];
        if (isset($response->segments) && is_iterable($response->segments)) {
            foreach ($response->segments as $segment) {
                if (is_array($segment)) {
                    $segments[] = (string) ($segment['text'] ?? '');
                } elseif (is_object($segment) && isset($segment->text)) {
                    $segments[] = $segment->text;
                }
            }
        }

        if ($segments === []) {
            $segments = [$response->text ?? ''];
        }

        return $segments;
    }
}
