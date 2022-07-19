<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Repository\UserRepository;
use App\Controller\RegistrationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{   
    #[Route('/', name: 'app_profile_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, RegistrationController $rc, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            // this condition is needed because field is not required
            // so file must be processed only when a file is uploaded
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                // Move the file to the directory where photos are stored
                try {
                    
                    $photoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    $this->addFlash('error', 'Error al subir la foto de perfil');
                }
                
                // Borra el fichero de imagen de perfil anterior si existe
                if ($user->getPhotoFilename()) {
                    $oldPhoto = $user->getPhotoFilename();
                    $oldPhotoPath = $this->getParameter('profile_photos_directory').'/'.$oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // updates the property to store the file name instead of its contents
                $user->setPhotoFilename($newFilename);
            }


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

            return $this->redirectToRoute('app_profile_show', [], Response::HTTP_SEE_OTHER);
        }

        // Almacena en un campo oculto el valor inicial del mail para recuperarlo después.
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
