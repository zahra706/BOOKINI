<?php

namespace App\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;
use App\Form\UserTypeForm;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    // Page de connexion
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère le dernier nom d'utilisateur saisi
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    // Page de déconnexion
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // La logique de déconnexion est gérée par Symfony, aucune action n'est nécessaire ici
    }

    
    #[Route('/register', name: 'app_register')]
public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
{
    $user = new User(); // Crée une nouvelle instance de User
    $form = $this->createForm(UserTypeForm::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Vérifie si l'email existe déjà dans la base de données
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

        if ($existingUser) {
            // Si l'email est déjà utilisé, on affiche un message d'erreur
            $this->addFlash('error', 'Cet email est déjà utilisé.');
            return $this->redirectToRoute('app_register');
        }

        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        // Définir le rôle en fonction du formulaire (user ou admin)
        $roles = $user->getRoles(); // Récupère les rôles du formulaire
        if (empty($roles)) {
            $user->setRoles(['ROLE_USER']); // Si aucun rôle n'est sélectionné, attribue par défaut ROLE_USER
        }

        // Persist l'utilisateur dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        // Redirige vers la page de connexion
        return $this->redirectToRoute('app_login');
    }

    return $this->render('register.html.twig', [
        'form' => $form->createView(),
    ]);
}

    

}
