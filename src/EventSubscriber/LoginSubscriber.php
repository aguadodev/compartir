<?php

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginSubscriber implements EventSubscriberInterface
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        // Obtiene el usuario que ha iniciado sesión
        $user = $event->getAuthenticatedToken()->getUser();
        // Actualiza la fecha de último acceso
        $user->setLastLogin(new \DateTimeImmutable());
        // Persiste el usuario en la base de datos
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
        ];
    }
}
