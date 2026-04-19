<?php

namespace App\Service\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class TwoFactorManager
{
    public function __construct(
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function ensureSecret(Utilisateur $user): void
    {
        if (empty($user->getTotpSecret())) {
            $user->setTotpSecret($this->totpAuthenticator->generateSecret());
            $this->entityManager->flush();
        }
    }

    public function getQrContent(Utilisateur $user): string
    {
        return $this->totpAuthenticator->getQRContent($user);
    }

    public function enableWithCode(Utilisateur $user, string $code): bool
    {
        if (!$this->totpAuthenticator->checkCode($user, $code)) {
            return false;
        }

        $user->setTwoFactorEnabled(true);
        $this->entityManager->flush();

        return true;
    }

    public function disable(Utilisateur $user): void
    {
        $user->setTwoFactorEnabled(false);
        $this->entityManager->flush();
    }
}
