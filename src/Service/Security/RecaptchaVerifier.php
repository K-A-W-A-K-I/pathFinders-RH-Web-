<?php

namespace App\Service\Security;

class RecaptchaVerifier
{
    public function __construct(
        ?string $secretKey,
        ?bool $enabled,
    ) {
        $this->secretKey = (string) ($secretKey ?? '');
        $this->enabled = (bool) ($enabled ?? false);
    }

    private string $secretKey;
    private bool $enabled;

    public function isEnabled(): bool
    {
        return $this->enabled && $this->secretKey !== '';
    }

    public function verify(?string $token, ?string $clientIp = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $payload = http_build_query([
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => $clientIp,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($raw === false) {
            return false;
        }

        $json = json_decode($raw, true);

        return (bool) ($json['success'] ?? false);
    }
}
