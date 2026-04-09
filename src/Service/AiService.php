<?php

namespace App\Service;

class AiService
{
    private const API_KEY = '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';
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODEL   = 'openai/gpt-3.5-turbo';

    // ── Generic chat call ──────────────────────────────────────────────────

    private function chat(string $prompt): string
    {
        $payload = json_encode([
            'model'    => self::MODEL,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::API_KEY,
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL error: ' . $err);
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? '';
    }

    // ── Generate QCM questions for an offre ───────────────────────────────

    /**
     * Returns array of questions:
     * [['question'=>'...','choix'=>['A','B','C','D'],'bonne_reponse'=>1,'points'=>2], ...]
     */
    public function generateQuestions(string $titre, string $description, string $domaine, int $count = 5): array
    {
        $prompt = <<<PROMPT
Tu es un expert RH. Génère exactement {$count} questions QCM pour évaluer un candidat au poste suivant :
Titre : {$titre}
Domaine : {$domaine}
Description : {$description}

Réponds UNIQUEMENT avec un JSON valide (tableau), sans texte avant ou après. Format :
[
  {
    "question": "...",
    "choix": ["choix1", "choix2", "choix3", "choix4"],
    "bonne_reponse": 1,
    "points": 2
  }
]
La bonne_reponse est l'index (1 à 4) du bon choix dans le tableau choix.
PROMPT;

        $raw = $this->chat($prompt);

        // Extract JSON from response (strip markdown code blocks if present)
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/```\s*$/m', '', $raw);
        $raw = trim($raw);

        $questions = json_decode($raw, true);
        if (!is_array($questions)) {
            throw new \RuntimeException('IA response is not valid JSON: ' . $raw);
        }

        return $questions;
    }

    // ── Analyse CV text against an offre ──────────────────────────────────

    /**
     * Returns ['score' => int(0-100), 'details' => string]
     */
    public function analyseCv(string $cvText, string $offreTitre, string $offreDescription, string $domaine): array
    {
        $prompt = <<<PROMPT
Tu es un expert RH. Analyse ce CV par rapport à l'offre d'emploi suivante et donne un score de compatibilité de 0 à 100.

Offre : {$offreTitre} ({$domaine})
Description de l'offre : {$offreDescription}

CV du candidat :
{$cvText}

Réponds UNIQUEMENT avec un JSON valide, sans texte avant ou après. Format :
{
  "score": 75,
  "details": "Explication courte en 2-3 phrases des points forts et faibles."
}
PROMPT;

        $raw = $this->chat($prompt);
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/```\s*$/m', '', $raw);
        $raw = trim($raw);

        $result = json_decode($raw, true);
        if (!isset($result['score'])) {
            throw new \RuntimeException('IA CV analysis response invalid: ' . $raw);
        }

        return [
            'score'   => (int) $result['score'],
            'details' => $result['details'] ?? '',
        ];
    }
}
