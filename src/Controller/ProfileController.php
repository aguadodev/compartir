<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use App\Controller\RegistrationController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{

    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }    

    #[Route('/', name: 'app_profile_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, RegistrationController $rc): Response
    {
        $user = $this->getUser();
        

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Comprueba si se ha modificado el email
            if ($user->getEmail() != $form->get('oldEmail')->getData()) {
                // Cambia el estado de verificado a falso
                $user->setIsVerified(false);
                // Envía un email de confirmación al nuevo email
                $rc->sendEmailConfirmation($user);    
                // e Informa al usuario con un mensaje flash
                $this->addFlash('success', 'Se ha enviado un email de confirmación a tu nuevo email');
            }             
            // Actualiza la fecha de modificación
            $user->setUpdatedAt(new \DateTimeImmutable());            
            $userRepository->add($user, true);
            // Comprueba si se ha modificado el email
           

            return $this->redirectToRoute('app_profile_show', [], Response::HTTP_SEE_OTHER);
        }
        $oldEmail = $user->getEmail();
        $form->get('oldEmail')->setData($oldEmail);

        return $this->renderForm('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, UserRepository $userRepository, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {

            // Cerrar sesión (Logout) => invalidate session
            $request->getSession()->invalidate();
            $tokenStorage->setToken();
            
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_index', [], Response::HTTP_SEE_OTHER);

    }
}
