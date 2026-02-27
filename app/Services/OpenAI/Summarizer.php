<?php

namespace App\Services\OpenAI;

use Exception;
use OpenAI\Client;
use Throwable;

class Summarizer implements \App\Services\Deps\Summarizer
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function summarizeImg(string $filePath, ?string $locale = null): string
    {
        $imageData = base64_encode(file_get_contents($filePath));
        $mimeType = mime_content_type($filePath);

        $prompt = trim(view('prompts.describe-image', ['locale' => $locale])->render());

        $response = $this->client->chat()->create([
            'model' => 'gpt-5-nano',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:$mimeType;base64,$imageData",
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    /**
     * @throws Exception|Throwable
     */
    public function summarizePdf(string $filePath, ?string $locale = null): string
    {
        $file = $this->client->files()->upload([
            'purpose' => 'user_data',
            'file' => fopen($filePath, 'r'),
        ]);

        $prompt = trim(view('prompts.summarize-document', ['locale' => $locale])->render());

        $response = $this->client->responses()->create([
            'model' => 'gpt-5-nano',
            'input' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $prompt],
                    [
                        'type' => 'input_file',
                        'file_id' => $file->id,
                    ],
                ],
            ]],
        ]);

        $this->client->files()->delete($file->id);
        return $response->outputText;
    }
}
