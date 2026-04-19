<?php

namespace App\Controller\User;

use App\Entity\Utilisateur;
use App\Service\Security\TwoFactorManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil/2fa', name: 'profile_2fa_')]
class TwoFactorController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TwoFactorManager $twoFactorManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('auth_login');
        }

        $twoFactorManager->ensureSecret($user);

        return $this->render('profile/2fa.html.twig', [
            'qr_content' => $twoFactorManager->getQrContent($user),
            'enabled' => $user->isTwoFactorEnabled(),
        ]);
    }

    #[Route('/enable', name: 'enable', methods: ['POST'])]
    public function enable(Request $request, TwoFactorManager $twoFactorManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('auth_login');
        }

        $code = trim((string) $request->request->get('code', ''));
        if ($code === '') {
            $this->addFlash('error', 'Le code TOTP est obligatoire.');
            return $this->redirectToRoute('profile_2fa_index');
        }

        $ok = $twoFactorManager->enableWithCode($user, $code);
        if (!$ok) {
            $this->addFlash('error', 'Code invalide. Vérifiez votre application Authenticator.');
            return $this->redirectToRoute('profile_2fa_index');
        }

        $this->addFlash('success', 'Authentification à deux facteurs activée.');
        return $this->redirectToRoute('profile_2fa_index');
    }

    #[Route('/disable', name: 'disable', methods: ['POST'])]
    public function disable(TwoFactorManager $twoFactorManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('auth_login');
        }

        $twoFactorManager->disable($user);
        $this->addFlash('success', 'Authentification à deux facteurs désactivée.');

        return $this->redirectToRoute('profile_2fa_index');
    }
}
