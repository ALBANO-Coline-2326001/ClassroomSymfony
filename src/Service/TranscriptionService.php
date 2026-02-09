<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class TranscriptionService
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(OPENAI_API_KEY)%')] private string $apiKey
    ) {}

    public function transcribeVideo(string $filePath): string
    {
        $formFields = [
            'file' => DataPart::fromPath($filePath),
            'model' => 'whisper-1',
            'language' => 'fr',
        ];

        $formData = new FormDataPart($formFields);

        $response = $this->client->request('POST', 'https://api.openai.com/v1/audio/transcriptions', [
            'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'multipart/form-data',
                ] + $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
            'timeout' => 300,
        ]);

        $content = $response->toArray();

        return $content['text'] ?? '';
    }
}