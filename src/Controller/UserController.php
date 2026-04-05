<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Utilisateur;
use App\Repository\CandidatRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/utilisateurs', name: 'user_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UtilisateurRepository $repo): Response
    {
        return $this->render('user/index.html.twig', [
            'utilisateurs' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator,
        UtilisateurRepository $repo
    ): Response {
        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $errors = $this->validateUserData($data, $validator, null, $repo);

            if (empty($errors)) {
                $user = new Utilisateur();
                $user->setNom($data['nom']);
                $user->setPrenom($data['prenom']);
                $user->setEmail($data['email']);
                $user->setTelephone($data['telephone'] ?? null);
                $user->setRole($data['role']);
                $user->setStatut($data['statut'] ?? 'actif');
                $user->setPassword($hasher->hashPassword($user, $data['password']));
                $em->persist($user);
                $em->flush();

                // If candidat, create Candidat profile
                if ($data['role'] === 'ROLE_CANDIDAT') {
                    $candidat = new Candidat();
                    $candidat->setIdUtilisateur($user->getId());
                    $em->persist($candidat);
                    $em->flush();
                }

                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('user_index');
            }
        }

        return $this->render('user/form.html.twig', [
            'title'  => 'Nouvel utilisateur',
            'errors' => $errors,
            'data'   => $data,
            'user'   => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        UtilisateurRepository $repo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator,
        CandidatRepository $candidatRepo
    ): Response {
        $user = $repo->find($id);
        if (!$user) throw $this->createNotFoundException();

        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data   = $request->request->all();
            $errors = $this->validateUserData($data, $validator, $user, $repo);

            if (empty($errors)) {
                $user->setNom($data['nom']);
                $user->setPrenom($data['prenom']);
                $user->setEmail($data['email']);
                $user->setTelephone($data['telephone'] ?? null);
                $user->setStatut($data['statut'] ?? 'actif');
                $oldRole = $user->getRole();
                $user->setRole($data['role']);

                if (!empty($data['password'])) {
                    $user->setPassword($hasher->hashPassword($user, $data['password']));
                }

                // Create Candidat if role changed to ROLE_CANDIDAT
                if ($data['role'] === 'ROLE_CANDIDAT' && $oldRole !== 'ROLE_CANDIDAT') {
                    if (!$candidatRepo->findByUserId($user->getId())) {
                        $candidat = new Candidat();
                        $candidat->setIdUtilisateur($user->getId());
                        $em->persist($candidat);
                    }
                }

                $em->flush();
                $this->addFlash('success', 'Utilisateur modifié.');
                return $this->redirectToRoute('user_index');
            }
        }

        return $this->render('user/form.html.twig', [
            'title'  => 'Modifier l\'utilisateur',
            'errors' => $errors,
            'data'   => $data,
            'user'   => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $repo->find($id);
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('user_index');
    }

    private function validateUserData(array $data, ValidatorInterface $validator, ?Utilisateur $existing = null, ?UtilisateurRepository $repo = null): array
    {
        $errors = [];

        if (empty($data['nom'])) $errors['nom'] = 'Le nom est obligatoire.';
        if (empty($data['prenom'])) $errors['prenom'] = 'Le prénom est obligatoire.';
        if (empty($data['email'])) {
            $errors['email'] = "L'email est obligatoire.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'email n'est pas valide.";
        } elseif ($repo) {
            $found = $repo->findOneBy(['email' => $data['email']]);
            if ($found && ($existing === null || $found->getId() !== $existing->getId())) {
                $errors['email'] = "Cette adresse email est déjà utilisée.";
            }
        }
        if (empty($data['role'])) $errors['role'] = 'Le rôle est obligatoire.';
        if ($existing === null && empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }

        return $errors;
    }
}
