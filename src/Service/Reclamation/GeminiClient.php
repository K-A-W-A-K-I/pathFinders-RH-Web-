<?php

namespace App\Service\Reclamation;

class GeminiClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds,
        private readonly bool $enabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->apiKey !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function generateJson(string $prompt): array
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('Gemini is disabled or missing API key.');
        }

        $url = sprintf('%s%s:generateContent?key=%s', self::BASE_URL, rawurlencode($this->model), rawurlencode($this->apiKey));
        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt,
                ]],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Gemini cURL error: ' . $curlError);
        }

        if (!is_string($rawResponse) || $rawResponse === '') {
            throw new \RuntimeException('Gemini returned an empty response.');
        }

        $decodedResponse = json_decode($rawResponse, true);
        if (!is_array($decodedResponse)) {
            throw new \RuntimeException('Gemini returned invalid JSON response payload.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $decodedResponse['error']['message'] ?? 'Unknown Gemini error';
            throw new \RuntimeException(sprintf('Gemini API error %d: %s', $httpCode, (string) $message));
        }

        $text = (string) ($decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '');
        if ($text === '') {
            throw new \RuntimeException('Gemini response did not contain generated content.');
        }

        $json = json_decode($text, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Gemini generated invalid JSON content.');
        }

        return $json;
    }
}
