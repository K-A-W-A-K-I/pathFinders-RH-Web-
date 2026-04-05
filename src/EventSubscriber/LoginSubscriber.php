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
        $roles = $user->getRoles();
        $rawRole = method_exists($user, 'getRole') ? $user->getRole() : '';
        if (in_array('ROLE_ADMIN', $roles) || in_array('admin', $roles) || $rawRole === 'admin'
            || in_array('ROLE_WORKER', $roles) || in_array('employe', $roles) || $rawRole === 'employe') {
            $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/offres'));
        } else {
            $event->setResponse(new \Symfony\Component\HttpFoundation\RedirectResponse('/'));
        }
    }
}
