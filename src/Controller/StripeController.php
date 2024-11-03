<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Stripe;

class StripeController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/stripe', name: 'app_stripe')]
    public function index(Request $request): Response
    {
        $total = $request->getSession()->get('total');
        return $this->render('stripe/index.html.twig', [
            'stripe_key' => $_ENV["STRIPE_KEY"],
            'total' => $total,
        ]);
    }

    #[Route('/stripe/create-charge', name: 'app_stripe_charge', methods: ['POST'])]
    public function createCharge(Request $request): Response
    {
        Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET"]);
        $total = $request->getSession()->get('total');

        // Effectuer le paiement
        Stripe\Charge::create([
            "amount" => $total * 100,
            "currency" => "eur",
            "source" => $request->request->get('stripeToken'),
            "description" => "Binaryboxtuts Payment Test"
        ]);

        // Supprimer les articles du panier de l'utilisateur après le paiement réussi
        $user = $this->getUser();
        if ($user) {
            $cartItems = $this->entityManager
                ->getRepository(Cart::class)
                ->findBy(['user' => $user]);

            // Créer une nouvelle commande
            $order = new Order();
            $order->setUser($user);
            $order->setOrderDate(new \DateTime());

            // Ajouter des articles à la commande
            foreach ($cartItems as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setProduct($cartItem->getProduct());
                $orderItem->setQuantity($cartItem->getQuantity());
                $order->addOrderItem($orderItem);

                // Supprimer l'article du panier
                $this->entityManager->remove($cartItem);
            }

            // Enregistrer la commande
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        }

        // Message de confirmation de paiement
        $this->addFlash('success', 'Payment Successful! Your cart has been cleared.');

        return $this->redirectToRoute('app_stripe', [], Response::HTTP_SEE_OTHER);
    }
}
