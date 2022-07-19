<?php

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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

    public function onCheckPassportEvent(CheckPassportEvent $event): void
    {
        // Obtiene el usuario que intenta iniciar sesión
        $user = $event->getPassport()->getUser();
        // Si el usuario no está habilitado lanzamos una excepción
        if (!$user->isEnabled()){
            throw new AuthenticationException('Usuario deshabilitado');
        } 
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
            CheckPassportEvent::class => 'onCheckPassportEvent',
        ];
    }
}
