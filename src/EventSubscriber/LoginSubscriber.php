<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        // Do not override API/JWT login responses. Lexik must return JSON { token: ... }.
        $request = $event->getRequest();
        $firewallName = $event->getFirewallName();
        if ($firewallName === 'api_login' || str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // If another authenticator already produced a response, keep it.
        if (null !== $event->getResponse()) {
            return;
        }

        $user    = $event->getAuthenticatedToken()->getUser();
        $session = $request->getSession();

        if (method_exists($user, 'getId')) {
            $session->set('user_id', $user->getId());
        }

        // Redirect by role
        $rawRole = method_exists($user, 'getRole') ? $user->getRole() : '';
        if (in_array($rawRole, ['admin', 'ROLE_ADMIN'])) {
            $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/offres'));
        } elseif (in_array($rawRole, ['employe', 'ROLE_WORKER'])) {
            $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse('/mes-fiches'));
        } else {
            $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse('/'));
        }
    }
}
