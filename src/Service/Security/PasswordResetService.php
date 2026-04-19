<?php

namespace App\Service\Security;

use App\Entity\Utilisateur;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class PasswordResetService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function issueToken(Utilisateur $user): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $user->setResetToken(hash('sha256', $rawToken));
        $user->setResetExpiry((new DateTimeImmutable())->add(new DateInterval('PT1H')));
        $this->entityManager->flush();

        return $rawToken;
    }

    public function isTokenValid(Utilisateur $user, string $rawToken): bool
    {
        $storedToken = $user->getResetToken();
        $expiry = $user->getResetExpiry();

        if (empty($storedToken) || empty($rawToken) || !$expiry instanceof \DateTimeInterface) {
            return false;
        }

        if ($expiry < new DateTimeImmutable()) {
            return false;
        }

        return hash_equals($storedToken, hash('sha256', $rawToken));
    }

    public function clearToken(Utilisateur $user): void
    {
        $user->setResetToken(null);
        $user->setResetExpiry(null);
        $this->entityManager->flush();
    }
}
