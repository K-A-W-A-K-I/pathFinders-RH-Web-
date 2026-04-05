<?php
 
namespace App\Controller;
 
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
 
class AuthController extends AbstractController
{
    #[Route('/login', name: 'auth_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
 
        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }
 
    #[Route('/login/check', name: 'auth_login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new LogicException('This route is handled by Symfony security.');
    }
 
    #[Route('/register', name: 'auth_register', methods: ['GET', 'POST'])]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }
 
    #[Route('/logout', name: 'auth_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new LogicException('This route is handled by Symfony security.');
    }
}