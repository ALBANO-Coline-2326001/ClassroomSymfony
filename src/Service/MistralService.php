<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MistralService
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(MISTRAL_API_KEY)%')] private string $apiKey
    ) {}

    public function generateQcmFromText(string $text): array
    {

        $text = substr($text, 0, 20000);

        $prompt = <<<EOT
            Tu es un expert pédagogique. Analyse le texte suivant et génère un QCM de 10 questions pertinentes.
            
            Règles strictes :
            1. La sortie DOIT être uniquement un JSON valide. Pas de markdown (```json), pas de texte avant ou après.
            2. Structure attendue :
            [
                {
                    "question": "L'intitulé de la question",
                    "answers": [
                        {"text": "Réponse A", "isCorrect": true},
                        {"text": "Réponse B", "isCorrect": false},
                        {"text": "Réponse C", "isCorrect": false},
                        {"text": "Réponse D", "isCorrect": false}
                    ]
                }
            ]
            3. Il doit y avoir exactement une bonne réponse par question.
            4. Utilise EXACTEMENT les clés "question", "answers", "text", "isCorrect".
            5. Renvoie UNIQUEMENT le JSON. Pas de "Voici le JSON", pas de markdown ```json.
            
            Texte à analyser :
            $text
EOT;

        $response = $this->client->request('POST', 'https://api.mistral.ai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'mistral-small-latest',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
            ],
        ]);

        $content = $response->toArray();
        $rawJson = $content['choices'][0]['message']['content'] ?? '';
        $rawJson = str_replace(['```json', '```'], '', $rawJson);

        return json_decode($rawJson, true) ?? [];

    }
}