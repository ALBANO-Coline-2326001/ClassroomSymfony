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

    public function generateQcmFromText(string $text, int $nbQuestions = 10, string $type = 'qcm'): array
    {
        $text = substr($text, 0, 100000);

        if ($type === 'vrai_faux') {
            $typeInstruction = "Chaque question doit être une affirmation. Les réponses doivent être uniquement 'Vrai' et 'Faux'.";
            $answersInstruction = '
                    "answers": [
                        {"text": "Vrai", "isCorrect": true},
                        {"text": "Faux", "isCorrect": false}
                    ]';
        } else {
            $typeInstruction = "Chaque question doit avoir 4 choix de réponse.";
            $answersInstruction = '
                    "answers": [
                        {"text": "Réponse A", "isCorrect": true},
                        {"text": "Réponse B", "isCorrect": false},
                        {"text": "Réponse C", "isCorrect": false},
                        {"text": "Réponse D", "isCorrect": false}
                    ]';
        }

        $prompt = <<<EOT
            Tu es un expert pédagogique. Analyse le texte suivant et génère un questionnaire de $nbQuestions questions pertinentes.
            
            Règles strictes :
            1. $typeInstruction
            2. La sortie DOIT être uniquement un JSON valide. Pas de markdown.
            3. Structure attendue pour chaque question :
            [
                {
                    "question": "L'intitulé de la question",
                    $answersInstruction
                }
            ]
            4. Il doit y avoir exactement une bonne réponse par question.
            5. Renvoie UNIQUEMENT le JSON pur.
            
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