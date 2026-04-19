<?php

namespace App\Service\Mailer;

use Psr\Log\LoggerInterface;

class BrevoMailer
{
    public function __construct(
        ?string $apiKey,
        ?string $fromEmail,
        ?string $fromName,
        private readonly LoggerInterface $logger,
    ) {
        $this->apiKey = (string) ($apiKey ?? '');
        $this->fromEmail = (string) ($fromEmail ?? '');
        $this->fromName = (string) ($fromName ?? 'PathFinders RH');
    }

    private string $apiKey;
    private string $fromEmail;
    private string $fromName;

    public function isEnabled(): bool
    {
        return $this->apiKey !== '' && $this->fromEmail !== '';
    }

    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        if (!$this->isEnabled()) {
            $this->logger->warning('Brevo password reset email is disabled due to missing API key or sender email.');
            return false;
        }

        $payload = [
            'sender' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName,
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => trim($toName),
            ]],
            'subject' => 'Réinitialisation de votre mot de passe',
            'htmlContent' => sprintf(
                '<p>Bonjour,</p><p>Vous avez demandé la réinitialisation de votre mot de passe.</p><p><a href="%s">Réinitialiser mon mot de passe</a></p><p>Ce lien expire dans 1 heure.</p><p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>',
                htmlspecialchars($resetUrl, ENT_QUOTES)
            ),
        ];

        return $this->sendPayload($payload, 'Brevo password reset email failed.', $toEmail);
    }

    public function sendReclamationReply(string $toEmail, string $toName, string $subjectRef, string $replyText): bool
    {
        if (!$this->isEnabled()) {
            $this->logger->warning('Brevo reclamation reply email is disabled due to missing API key or sender email.');
            return false;
        }

        $payload = [
            'sender' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName,
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => trim($toName),
            ]],
            'subject' => 'Réponse à votre réclamation - ' . $subjectRef,
            'htmlContent' => sprintf(
                '<p>Bonjour %s,</p><p>Nous faisons suite à votre réclamation concernant: <strong>%s</strong>.</p><p>%s</p><p>Nous restons à votre disposition pour tout complément.</p><p>Cordialement,<br>%s</p>',
                htmlspecialchars($toName !== '' ? $toName : 'Madame, Monsieur', ENT_QUOTES),
                htmlspecialchars($subjectRef, ENT_QUOTES),
                nl2br(htmlspecialchars($replyText, ENT_QUOTES)),
                htmlspecialchars($this->fromName, ENT_QUOTES)
            ),
        ];

        return $this->sendPayload($payload, 'Brevo reclamation reply email failed.', $toEmail);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function sendPayload(array $payload, string $errorMessage, string $recipient): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'accept: application/json',
                    'content-type: application/json',
                    'api-key: ' . $this->apiKey,
                ]) . "\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $context);
        $statusLine = $http_response_header[0] ?? '';
        $statusCode = 0;
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            $statusCode = (int) $matches[1];
        }

        if ($result === false || $statusCode < 200 || $statusCode >= 300) {
            $this->logger->error($errorMessage, [
                'status' => $statusCode,
                'status_line' => $statusLine,
                'recipient' => $recipient,
                'response' => is_string($result) ? $result : null,
            ]);

            return false;
        }

        return true;
    }
}
