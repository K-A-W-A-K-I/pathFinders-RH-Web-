<?php

namespace App\Controller\User;

use App\Repository\CandidatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil', name: 'profile_')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(CandidatRepository $candidatRepo): Response
    {
        $user     = $this->getUser();
        $candidat = $user ? $candidatRepo->findByUserId($user->getId()) : null;

        return $this->render('profile/index.html.twig', [
            'user'    => $user,
            'candidat'=> $candidat,
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        CandidatRepository $candidatRepo
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

                // Handle CV upload
                $cvFile = $request->files->get('cv');
                if ($cvFile && $candidat) {
                    $cvDir  = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
                    if (!is_dir($cvDir)) mkdir($cvDir, 0777, true);
                    $cvName = 'cv_' . $user->getId() . '_' . time() . '.' . $cvFile->guessExtension();
                    $cvFile->move($cvDir, $cvName);
                    $candidat->setCvPath('uploads/cv/' . $cvName);
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
