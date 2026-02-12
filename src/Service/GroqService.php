<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class GroqService
{
    private bool $isConfigured;

    private const BASE_URL = 'https://api.groq.com/openai/v1';

    private const CHAT_MODEL = 'llama-3.3-70b-versatile';
    private const AUDIO_MODEL = 'whisper-large-v3';

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(GROQ_API_KEY)%')] private ?string $apiKey
    ) {
        $this->isConfigured = !empty($this->apiKey);
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function generateQcmFromVideoFile(string $filePath, int $nbQuestions = 10, string $type = 'qcm'): array
    {
        if (!$this->isConfigured) return [];

        try {
            $transcription = $this->transcribeAudioWithGroq($filePath);

            if (empty($transcription)) {
                throw new \Exception("La transcription de la vidéo est vide.");
            }

            return $this->generateQcmFromText($transcription, $nbQuestions, $type);

        } catch (\Exception $e) {
            return [];
        }
    }
    private function transcribeAudioWithGroq(string $filePath): string
    {
        $formFields = [
            'file' => DataPart::fromPath($filePath),
            'model' => self::AUDIO_MODEL,
            'response_format' => 'json'
        ];

        $formData = new FormDataPart($formFields);

        $response = $this->client->request('POST', self::BASE_URL . '/audio/transcriptions', [
            'headers' => array_merge(
                [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ],
                $formData->getPreparedHeaders()->toArray()
            ),
            'body' => $formData->bodyToIterable(),
            'timeout' => 300
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Erreur Groq Audio : " . $response->getContent(false));
        }

        $data = $response->toArray();
        return $data['text'] ?? '';
    }

    /**
     * Génère le JSON des questions à partir du texte
     */
    private function generateQcmFromTextO(string $contextText, int $nbQuestions, string $type): array
    {
        if ($type === 'vrai_faux') {
            $rules = "Réponses possibles : 'Vrai' ou 'Faux'.";
            $jsonStructure = '{"text": "Vrai", "isCorrect": true}, {"text": "Faux", "isCorrect": false}';
        } else {
            $rules = "4 choix de réponse par question.";
            $jsonStructure = '{"text": "A", "isCorrect": true}, {"text": "B", "isCorrect": false}, ...';
        }

        $contextText = substr($contextText, 0, 15000);

        $systemPrompt = <<<EOT
        Tu es un expert pédagogique. Crée un QCM de $nbQuestions questions basé sur le texte fourni.
        Règles :
        1. $rules
        2. Sortie STRICTEMENT en JSON valide (tableau d'objets).
        3. Structure par question :
        {
            "question": "L'intitulé...",
            "answers": [ $jsonStructure ]
        }
        4. Langue : Français.
        EOT;

        $response = $this->client->request('POST', self::BASE_URL . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::CHAT_MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Voici le contenu du cours :\n\n" . $contextText]
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object']
            ]
        ]);

        $content = $response->toArray();
        $rawJson = $content['choices'][0]['message']['content'] ?? '[]';

        $jsonString = str_replace(['```json', '```'], '', $rawJson);

        $decoded = json_decode($jsonString, true);

        if (isset($decoded['questions'])) {
            return $decoded['questions'];
        }

        return $decoded ?? [];
    }

    /**
     * Génère le JSON des questions à partir du texte
     */
    private function generateQcmFromText(string $contextText, int $nbQuestions, string $type): array
    {
        // 1. SÉCURITÉ : On réduit drastiquement la taille du texte
        // Le modèle 8192 accepte environ ~24k caractères max en entrée/sortie combinées.
        // On coupe à 10 000 caractères pour être LARGE et laisser de la place à la réponse.
        $contextText = substr($contextText, 0, 10000);

        if ($type === 'vrai_faux') {
            $rules = "Réponses possibles : 'Vrai' ou 'Faux'.";
            $jsonStructure = '{"text": "Vrai", "isCorrect": true}, {"text": "Faux", "isCorrect": false}';
        } else {
            $rules = "4 choix de réponse par question.";
            $jsonStructure = '{"text": "A", "isCorrect": true}, {"text": "B", "isCorrect": false}, ...';
        }

        $systemPrompt = <<<EOT
        Tu es un expert pédagogique. Crée un QCM de $nbQuestions questions basé sur le texte fourni.
        Règles :
        1. $rules
        2. Sortie STRICTEMENT en JSON valide (tableau d'objets).
        3. Structure par question :
        {
            "question": "L'intitulé...",
            "answers": [ $jsonStructure ]
        }
        4. IMPORTANT : Ne mets RIEN d'autre que le JSON. Pas de "Voici le QCM", pas de balises markdown ```.
        EOT;

        try {
            $response = $this->client->request('POST', self::BASE_URL . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::CHAT_MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Analyse ce cours et génère le QCM :\n\n" . $contextText]
                    ],
                    'temperature' => 0.3,
                    // 'response_format' => ['type' => 'json_object'] // <-- JE L'AI COMMENTÉ, C'EST SOUVENT LA CAUSE DU BUG 400
                ]
            ]);

            $content = $response->toArray(); // Cela déclenchera une exception si c'est encore 400

            $rawJson = $content['choices'][0]['message']['content'] ?? '[]';

            // Nettoyage manuel (plus robuste que le mode JSON strict parfois)
            $rawJson = str_replace(['```json', '```'], '', $rawJson);
            $start = strpos($rawJson, '[');
            $end = strrpos($rawJson, ']');

            if ($start !== false && $end !== false) {
                $rawJson = substr($rawJson, $start, $end - $start + 1);
            }

            $decoded = json_decode($rawJson, true);

            if (isset($decoded['questions'])) {
                return $decoded['questions'];
            }

            return $decoded ?? [];

        } catch (\Exception $e) {
            // C'est ICI que tu vas voir pourquoi c'est rouge/orange
            // Si tu as une erreur, Symfony va te l'afficher grâce au dd()
            if (method_exists($e, 'getResponse')) {
                dd("ERREUR GROQ LLAMA : ", $e->getResponse()->getContent(false));
            }
            dd($e->getMessage());
        }
    }
}