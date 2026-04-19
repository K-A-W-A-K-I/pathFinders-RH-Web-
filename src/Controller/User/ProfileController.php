<?php

namespace App\Controller\User;

use App\Repository\CandidatRepository;
use App\Service\CloudinaryUploader;
use App\Service\UserPdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil', name: 'profile_')]
class ProfileController extends AbstractController
{
    #[Route('/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(CandidatRepository $candidatRepo, UserPdfGenerator $userPdfGenerator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $candidat = $candidatRepo->findByUserId((int) $user->getId());
        $pdf = $userPdfGenerator->generateUserProfilePdf($user, $candidat);

        $filename = sprintf('mon_profil_%d_%s.pdf', (int) $user->getId(), date('Ymd_His'));

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        CandidatRepository $candidatRepo,
        CloudinaryUploader $cloudinaryUploader,
    ): Response
    {
        return $this->edit($request, $em, $hasher, $candidatRepo, $cloudinaryUploader);
    }

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        CandidatRepository $candidatRepo,
        CloudinaryUploader $cloudinaryUploader,
    ): Response {
        $user     = $this->getUser();
        $candidat = $user ? $candidatRepo->findByUserId($user->getId()) : null;
        $errors   = [];

        if ($request->isMethod('POST')) {
            $nom      = trim($request->request->get('nom', ''));
            $prenom   = trim($request->request->get('prenom', ''));
            $tel      = trim($request->request->get('telephone', ''));
            $password = $request->request->get('password', '');
            $lettre   = trim($request->request->get('lettre_motivation', ''));

            if (empty($nom))    $errors['nom']    = 'Le nom est obligatoire.';
            if (empty($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
            if (!empty($password) && strlen($password) < 6) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }

            if (empty($errors)) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setTelephone($tel ?: null);
                if (!empty($password)) {
                    $user->setPassword($hasher->hashPassword($user, $password));
                }

                $photoFile = $request->files->get('photo');
                $cvFile = $request->files->get('cv');

                try {
                    if ($photoFile) {
                        $oldImageUrl = $user->getImageUrl();
                        $upload = $cloudinaryUploader->uploadProfileImage($photoFile, (int) $user->getId());
                        if ($upload['secure_url'] === '') {
                            throw new \RuntimeException('Echec de l\'upload de l\'image de profil.');
                        }
                        $user->setImageUrl($upload['secure_url']);
                        $cloudinaryUploader->deleteByUrl($oldImageUrl, 'image');
                    }

                    if ($cvFile && $candidat) {
                        $oldCvUrl = $candidat->getCvPath();
                        $upload = $cloudinaryUploader->uploadCv($cvFile, (int) $user->getId());
                        if ($upload['secure_url'] === '') {
                            throw new \RuntimeException('Echec de l\'upload du CV.');
                        }
                        $candidat->setCvPath($upload['secure_url']);
                        $cloudinaryUploader->deleteByUrl($oldCvUrl, 'image');
                    }
                } catch (\InvalidArgumentException $exception) {
                    $this->addFlash('error', $exception->getMessage());
                    return $this->render('profile/edit.html.twig', [
                        'user'    => $user,
                        'candidat'=> $candidat,
                        'errors'  => $errors,
                    ]);
                } catch (\Throwable) {
                    $this->addFlash('error', 'Upload CV/image refusé par le service cloud. Vérifiez le format, la taille, puis réessayez.');
                    return $this->render('profile/edit.html.twig', [
                        'user'    => $user,
                        'candidat'=> $candidat,
                        'errors'  => $errors,
                    ]);
                }

                if ($candidat && !empty($lettre)) {
                    $candidat->setLettreMotivation($lettre);
                }

                $em->flush();
                $this->addFlash('success', 'Profil mis à jour.');
                return $this->redirectToRoute('profile_index');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user'    => $user,
            'candidat'=> $candidat,
            'errors'  => $errors,
        ]);
    }
}
