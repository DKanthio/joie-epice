<?php


namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="forgot_password")
     */
    public function forgotPassword(Request $request): Response
    {
        // Vérifier si le formulaire de réinitialisation de mot de passe a été soumis
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email'); // Récupérer l'e-mail soumis depuis le formulaire

            // Vérifier si l'e-mail existe dans la base de données (vous devez implémenter cette logique)
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
    

            if ($user) {
                // Si l'e-mail existe, rediriger vers une page de confirmation de réinitialisation de mot de passe
                return $this->redirectToRoute('confirm_reset_password', ['email' => $email]);
            } else {
                // Si l'e-mail n'existe pas, afficher un message d'erreur
                $this->addFlash('error', 'Adresse e-mail non valide.');
            }
        }
       
    
        // Afficher le formulaire de saisie de l'e-mail
        return $this->render('auth/forgot_password.html.twig', [
           
        ]);
    }

    /**
     * @Route("/confirmer-reset-mot-de-passe/{email}", name="confirm_reset_password")
     */
    public function confirmResetPassword(Request $request, string $email, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        // Vérifier si le formulaire de confirmation de réinitialisation de mot de passe a été soumis
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password'); // Récupérer le nouveau mot de passe soumis depuis le formulaire

            // Mettre à jour le mot de passe dans la base de données
            $entityManager = $this->entityManager;
            $user = $entityManager->getRepository(User::class)->findOneByEmail($email);
             
            if ($user) {
                // Hasher le nouveau mot de passe
                $hashedPassword = $userPasswordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                // Mettre à jour l'entité utilisateur en base de données
                $entityManager->persist($user);
                $entityManager->flush();

                // Rediriger vers la page de connexion
                return $this->redirectToRoute('login');
            } else {
                // Si l'utilisateur n'est pas trouvé, afficher un message d'erreur
                $this->addFlash('error', 'Utilisateur non trouvé.');
            }
        }
       
    
        // Afficher le formulaire de saisie du nouveau mot de passe
        return $this->render('auth/confirm_reset_password.html.twig', [
            'email' => $email,
            
        ]);
    }
}

