<?php

namespace App\Controller;

// Importamos las clases necesarias para enviar correos
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
//...
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MailController extends AbstractController
{
    #[Route('/mail', name: 'app_mail')]
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('hello@example.com')
            ->to('you@example.com')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Enviando correo de prueba desde la aplicación!')
            ->text('Contenido en texto plano!')
            ->html('<p>Contenido en formato HTML. Se pueden aplicar plantillas Twig para mayor flexibilidad y reutilización</p>');
 
        $mailer->send($email);
       
        dd($email);
        // Pasamos el email a la vista para mostrar los datos
        return $this->render('mail/index.html.twig', ['email' => $email]);
    }


}
