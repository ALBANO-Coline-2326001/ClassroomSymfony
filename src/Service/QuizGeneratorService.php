<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class QuizGeneratorService {
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $iaApiKey
    ){}

    public function generateQuiz(string $text): array {
        $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->iaApiKey, [
        'json' => [
            'content' => [
                ['parts' => [['text' => "Génère un QCM JSON à partir de : " . $text]]]
            ]
        ]
        ]);
        return $response->toArray();
    }
}
