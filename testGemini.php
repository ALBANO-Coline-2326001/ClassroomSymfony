<?php
require 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$apiKey = 'AIzaSyCPVci2JrXeddSEcYBiLGb3EUJfgstutCg'; // Remplace par ta clé (celle qui commence par AIza)
$client = HttpClient::create();

try {
    echo "🔍 Test de connexion à l'API Gemini...\n";

    // On fait un simple GET pour lister les modèles disponibles
    $response = $client->request('GET', "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

    $statusCode = $response->getStatusCode();
    echo "Statut HTTP : $statusCode \n";

    if ($statusCode === 200) {
        $data = $response->toArray();
        echo "✅ Connexion réussie ! Voici les modèles disponibles pour toi :\n";
        foreach ($data['models'] as $model) {
            echo "- " . $model['name'] . "\n";
        }
    } else {
        echo "❌ Erreur : " . $response->getContent(false);
    }

} catch (\Exception $e) {
    echo "💥 Exception : " . $e->getMessage();
}