<?php

namespace App\Services\OpenAI;

use Exception;
use Illuminate\Support\Collection;
use OpenAI;
use Throwable;

class Assistant implements \App\Services\Deps\Assistant
{
    protected Summarizer $summarizer;

    protected Transcriptor $transcriptor;

    protected Notetaker $notetaker;

    protected string $locale;

    /**
     * @throws Exception
     */
    public function __construct(?string $locale = null)
    {
        $this->locale = $locale ?? app()->getLocale();

        $apiKey = KeyForOpenAI::get();
        if (! $apiKey) {
            throw new Exception('OpenAI API key not found.');
        }

        $client = OpenAI::client($apiKey);
        $this->summarizer = new Summarizer($client);
        $this->transcriptor = new Transcriptor($client);
        $this->notetaker = new Notetaker($client);
    }

    /**
     * @throws Exception|Throwable
     */
    public function summarizeDoc(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $summary = $this->summarizer->summarizeImg($filePath, $this->locale);
        } elseif ($extension === 'pdf') {
            $summary = $this->summarizer->summarizePdf($filePath, $this->locale);
        } else {
            throw new Exception('Unsupported file type: '.$extension);
        }

        return $summary;
    }

    /**
     * @throws Exception
     */
    public function transcribeAudio(string $filePath): array
    {
        $segments = $this->transcriptor->transcribe($filePath, $this->locale);
        $segments = array_map('trim', $segments);

        return Hallucinations::filterForLocale($segments, $this->locale);
    }

    /**
     * @throws Exception
     */
    public function improveSegments(Collection $segments): array
    {
        return $this->notetaker->improveSegments($segments, $this->locale);
    }
}
