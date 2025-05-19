<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AiService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client,  #[Autowire('%env(OPENAI_API_KEY)%')] string $openaiApiKey)
    {
        $this->client = $client;
        $this->apiKey = $openaiApiKey;
    }

    public function analyseLivre($livre): string
{
    $content = $livre->getContent() ?? 'Contenu non disponible';
    $title = $livre->getTitre();

    $prompt = "Voici un livre intitulé « $title ». Voici son contenu :\n\n$content\n\nPeux-tu générer un résumé clair et concis de ce livre en français ?";

    try {
        // Utilisation de l'URL correcte pour OpenAI
        $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4',
                'prompt' => $prompt,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ],
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['text'] ?? 'Aucun résumé généré.';
    } catch (\Exception $e) {
        return 'Erreur d\'analyse avec ChatGPT : ' . $e->getMessage();
    }
}

}
