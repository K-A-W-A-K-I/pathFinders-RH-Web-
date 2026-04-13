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
        $user    = $event->getAuthenticatedToken()->getUser();
        $session = $event->getRequest()->getSession();

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
