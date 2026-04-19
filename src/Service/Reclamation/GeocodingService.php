<?php

namespace App\Service\Reclamation;

class GeocodingService
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $userAgent,
        private readonly int $timeoutSeconds,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array{latitude: float, longitude: float, display_name: string}|null
     */
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if (!$this->enabled || $query === '') {
            return null;
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $raw = $this->request($url);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded[0])) {
            return null;
        }

        $item = $decoded[0];

        return [
            'latitude' => (float) ($item['lat'] ?? 0),
            'longitude' => (float) ($item['lon'] ?? 0),
            'display_name' => (string) ($item['display_name'] ?? $query),
        ];
    }

    private function request(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ' . $this->userAgent,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Erreur geocodage: ' . $curlError);
        }

        if (!is_string($response) || $response === '') {
            throw new \RuntimeException('Reponse geocodage vide.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Le service de geocodage a retourne le code ' . $httpCode);
        }

        return $response;
    }
}
