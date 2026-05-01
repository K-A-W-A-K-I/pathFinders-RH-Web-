<?php

namespace App\Controller\User;

use App\Entity\Candidat;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Service\Mailer\BrevoMailer;
use App\Service\Security\PasswordResetService;
use App\Service\Security\RecaptchaVerifier;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'auth_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectByRole($this->getUser());
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error'         => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/connexion/check', name: 'auth_login_check')]
    public function loginCheck(): never
    {
        throw new \LogicException('Handled by Symfony security.');
    }

    #[Route('/deconnexion', name: 'auth_logout')]
    public function logout(): never
    {
        throw new \LogicException('Handled by Symfony security.');
    }

    #[Route('/inscription', name: 'auth_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        RecaptchaVerifier $recaptchaVerifier
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaToken = (string) $request->request->get('g-recaptcha-response', '');
            if (!$recaptchaVerifier->verify($recaptchaToken, $request->getClientIp())) {
                $this->addFlash('error', 'Validation reCAPTCHA invalide. Veuillez réessayer.');
                return $this->render('auth/register.html.twig', [
                    'form' => $form,
                    'recaptcha_site_key' => $this->getParameter('app.recaptcha.site_key'),
                ]);
            }

            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setRole('ROLE_CANDIDAT');
            $user->setStatut('actif');
            $em->persist($user);
            $em->flush();

            $candidat = new Candidat();
            $candidat->setIdUtilisateur($user->getId());
            $em->persist($candidat);
            $em->flush();

            $this->addFlash('success', 'Compte créé ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form,
            'recaptcha_site_key' => $this->getParameter('app.recaptcha.site_key'),
        ]);
    }

    #[Route('/mot-de-passe/oubli', name: 'auth_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        PasswordResetService $passwordResetService,
        BrevoMailer $brevoMailer,
        RecaptchaVerifier $recaptchaVerifier,
        #[Autowire('%env(default::APP_BASE_URL)%')] string $appBaseUrl,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Session expirée. Veuillez réessayer.');
                return $this->redirectToRoute('auth_forgot_password');
            }

            $email = trim((string) $request->request->get('email', ''));
            $captchaToken = (string) $request->request->get('g-recaptcha-response', '');

            if (!$recaptchaVerifier->verify($captchaToken, $request->getClientIp())) {
                $this->addFlash('error', 'Validation reCAPTCHA invalide.');

                return $this->render('auth/forgot_password.html.twig', [
                    'recaptcha_site_key' => $this->getParameter('app.recaptcha.site_key'),
                    'email' => $email,
                ]);
            }

            $user = $utilisateurRepository->findOneBy(['email' => $email]);
            if ($user instanceof Utilisateur) {
                $rawToken = $passwordResetService->issueToken($user);
                $baseUrl = $appBaseUrl !== '' ? rtrim($appBaseUrl, '/') : $request->getSchemeAndHttpHost();
                $resetUrl = sprintf('%s%s?token=%s&email=%s', $baseUrl, $this->generateUrl('auth_reset_password'), urlencode($rawToken), urlencode($user->getEmail()));
                $brevoMailer->sendPasswordReset($user->getEmail(), $user->getFullName(), $resetUrl);
            }

            // Always return a generic success message to avoid account enumeration.
            $this->addFlash('success', 'Si ce compte existe, un lien de réinitialisation a été envoyé.');

            return $this->redirectToRoute('auth_forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig', [
            'recaptcha_site_key' => $this->getParameter('app.recaptcha.site_key'),
            'email' => '',
        ]);
    }

    #[Route('/mot-de-passe/reset', name: 'auth_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        PasswordResetService $passwordResetService,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $email = trim((string) $request->query->get('email', $request->request->get('email', '')));
        $token = trim((string) $request->query->get('token', $request->request->get('token', '')));

        $user = $email !== '' ? $utilisateurRepository->findOneBy(['email' => $email]) : null;
        $isValidToken = $user instanceof Utilisateur && $passwordResetService->isTokenValid($user, $token);

        if (!$isValidToken) {
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré.');

            return $this->redirectToRoute('auth_forgot_password');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Session expirée. Veuillez réessayer.');
                return $this->redirectToRoute('auth_forgot_password');
            }

            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            } elseif ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $password));
                $passwordResetService->clearToken($user);
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');

                return $this->redirectToRoute('auth_login');
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    #[Route('/redirect-after-login', name: 'auth_redirect')]
    public function redirectAfterLogin(
        SessionInterface $session, 
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em
    ): Response
    {
        $token = $tokenStorage->getToken();
        if ($token instanceof TwoFactorTokenInterface) {
            return $this->redirectToRoute('2fa_login');
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $session->set('user_id', method_exists($user, 'getId') ? $user->getId() : null);

        // Auto-create Candidat record for ROLE_CANDIDAT users if it doesn't exist
        if (in_array('ROLE_CANDIDAT', $user->getRoles())) {
            $candidat = $em->getRepository(Candidat::class)->findOneBy(['idUtilisateur' => $user->getId()]);
            if (!$candidat) {
                $candidat = new Candidat();
                $candidat->setIdUtilisateur($user->getId());
                $em->persist($candidat);
                $em->flush();
            }
        }

        $targetUrl = $session->get('_security.main.target_path');
        if ($targetUrl) {
            $session->remove('_security.main.target_path');
            return $this->redirect($targetUrl);
        }

        return $this->redirectByRole($user);
    }

    private function redirectByRole(object $user): Response
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->redirectToRoute('dashboard_home');
        }
        if (in_array('ROLE_WORKER', $roles)) {
            return $this->redirectToRoute('worker_about');
        }
        return $this->redirectToRoute('offre_list');
    }
}