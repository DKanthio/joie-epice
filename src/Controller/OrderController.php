<?php


namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/order/history', name: 'order_history')]
    public function history(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('login');
        }

        // Vérifier si l'utilisateur est un administrateur
        if ($this->isGranted('ROLE_ADMIN')) {
            // Si l'utilisateur est un admin, récupérer toutes les commandes
            $orders = $this->entityManager->getRepository(Order::class)->findAll();
        } else {
            // Sinon, récupérer seulement les commandes de l'utilisateur connecté
            $orders = $this->entityManager->getRepository(Order::class)->findBy(['user' => $user]);
        }

        return $this->render('order/history.html.twig', [
            'orders' => $orders,
        ]);
    }
}


