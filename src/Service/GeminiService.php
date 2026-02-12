<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiService
{
    private bool $isConfigured;

    // Constantes pour l'API
    private const BASE_URL = 'https://generativelanguage.googleapis.com';
    private const MODEL_NAME = 'gemini-2.0-flash';

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(default::GEMINI_API_KEY)%')] private ?string $apiKey
    ) {
        $this->isConfigured = !empty($this->apiKey);
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Point d'entrée principal
     */
    public function generateQcmFromVideo(string $videoPath, int $nbQuestions = 10, string $type = 'qcm'): array
    {
        if (!$this->isConfigured) {
            return [];
        }

        try {
            $fileUri = $this->uploadFile($videoPath);
            $this->waitForFileProcessing($fileUri);
            return $this->generateContent($fileUri, $nbQuestions, $type);

        } catch (\Exception $e) {
            dd([
                'ERREUR' => $e->getMessage(),
                'TRACE' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Upload le fichier en streaming binaire (évite de saturer la RAM PHP)
     */
    private function uploadFile(string $filePath): string
    {
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'video/mp4';

        $url = self::BASE_URL . '/upload/v1beta/files?key=' . $this->apiKey;

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'X-Goog-Upload-Protocol' => 'raw',
                'X-Goog-Upload-Header-Content-Length' => $fileSize,
                'Content-Type' => $mimeType
            ],
            'body' => fopen($filePath, 'r'),
            'timeout' => 600
        ]);

        $data = $response->toArray();
        return $data['file']['uri'];
    }

    /**
     * Vérifie l'état du fichier toutes les 2 secondes
     */
    private function waitForFileProcessing(string $fileUri): void
    {
        $state = 'PROCESSING';
        $attempts = 0;
        $maxAttempts = 30;

        while ($state === 'PROCESSING' && $attempts < $maxAttempts) {
            sleep(2);

            $response = $this->client->request('GET', $fileUri . '?key=' . $this->apiKey);
            $data = $response->toArray();

            $state = $data['state'] ?? 'FAILED';
            $attempts++;

            if ($state === 'FAILED') {
                throw new \Exception("Le traitement de la vidéo par Google a échoué.");
            }
        }

        if ($state !== 'ACTIVE') {
            throw new \Exception("Timeout: La vidéo met trop de temps à être traitée.");
        }
    }

    /**
     * Envoie le prompt une fois la vidéo prête
     */
    private function generateContent(string $fileUri, int $nbQuestions, string $type): array
    {
        if ($type === 'vrai_faux') {
            $typeInstruction = "Chaque question doit être une affirmation. Les réponses doivent être uniquement 'Vrai' et 'Faux'.";
            $answersStructure = '
            "answers": [
                {"text": "Vrai", "isCorrect": true},
                {"text": "Faux", "isCorrect": false}
            ]';
        } else {
            $typeInstruction = "Chaque question doit avoir 4 choix de réponse.";
            $answersStructure = '
            "answers": [
                {"text": "Réponse A", "isCorrect": true},
                {"text": "Réponse B", "isCorrect": false},
                {"text": "Réponse C", "isCorrect": false},
                {"text": "Réponse D", "isCorrect": false}
            ]';
        }

        $prompt = <<<EOT
            Tu es un expert pédagogique. Analyse la vidéo fournie et génère un questionnaire de $nbQuestions questions pertinentes.
            
            Règles strictes :
            1. $typeInstruction
            2. Sortie UNIQUEMENT en JSON brut (pas de markdown, pas de ```json).
            3. Structure JSON attendue pour chaque question :
            [
                {
                    "question": "Intitulé de la question",
                    $answersStructure
                }
            ]
            4. Il doit y avoir exactement une bonne réponse par question.
            5. Langue : Français.
EOT;

        $url = self::BASE_URL . '/v1beta/models/' . self::MODEL_NAME . ':generateContent?key=' . $this->apiKey;

        $response = $this->client->request('POST', $url, [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'file_data' => [ // C'est ici la magie : on pointe vers le fichier cloud
                                    'mime_type' => 'video/mp4',
                                    'file_uri' => $fileUri
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json'
                ]
            ],
            'timeout' => 60
        ]);

        $content = $response->toArray();
        $rawJson = $content['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

        $rawJson = str_replace(['```json', '```'], '', $rawJson);

        return json_decode($rawJson, true) ?? [];
    }
}