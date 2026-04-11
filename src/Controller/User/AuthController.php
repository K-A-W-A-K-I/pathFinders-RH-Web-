<?php

namespace App\Controller\User;

use App\Entity\Candidat;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

        return $this->render('auth/register.html.twig', ['form' => $form]);
    }

   #[Route('/redirect-after-login', name: 'auth_redirect')]
public function redirectAfterLogin(SessionInterface $session, Request $request): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('auth_login');
    }

    // 🔍 DEBUG - voir toutes les clés de session
    dump($session->all());
    dump($session->get('_security.main.target_path'));
    die();

    $session->set('user_id', method_exists($user, 'getId') ? $user->getId() : null);

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
        if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_WORKER', $roles)) {
            return $this->redirectToRoute('offre_index');
        }
        return $this->redirectToRoute('offre_list');
    }
}