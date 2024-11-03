<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/add-to-cart/{productId}', name: 'add_to_cart')]
    public function addToCart(int $productId, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($productId);
    
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
    
        // Vérifier le stock disponible
        if ($product->getStock() <= 0) {
            $this->addFlash('error', 'Produit en rupture de stock');
            return $this->redirectToRoute('cart');
        }
    
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }
    
        // Chercher ou créer un article dans le panier
        $cartItem = $this->entityManager
            ->getRepository(Cart::class)
            ->findOneBy(['user' => $user, 'product' => $product]);
    
        if ($cartItem) {
            $cartItem->setQuantity($cartItem->getQuantity() + 1);
        } else {
            $cartItem = new Cart();
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
        }
    
        // Déduire du stock
        $product->setStock($product->getStock() - 1);
    
        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();
    
        return $this->redirectToRoute('cart');
    }
    

 
    #[Route('/cart', name: 'cart')]
    public function viewCart(Request $request): Response
    {
        $user = $this->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('login');
        }
    
        $cartItems = $this->entityManager
            ->getRepository(Cart::class)
            ->findBy(['user' => $user]);
    
        $total = array_reduce($cartItems, function($carry, Cart $cart) {
            return $carry + ($cart->getProduct()->getPrice() * $cart->getQuantity());
        }, 0);
    
        // Enregistrez le total dans la session
        $request->getSession()->set('total', $total);
    
        return $this->render('home/cart.html.twig', [
            'cart' => $cartItems,
            'total' => $total
        ]);
    }
    #[Route('/remove-from-cart/{id}', name: 'remove_from_cart')]
    public function removeFromCart(int $id): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }
    
        $cartItem = $this->entityManager
            ->getRepository(Cart::class)
            ->find($id);
    
        if ($cartItem && $cartItem->getUser() === $user) {
            // Récupérer le produit et restaurer la quantité
            $product = $cartItem->getProduct();
            $product->setStock($product->getStock() + $cartItem->getQuantity());
    
            $this->entityManager->remove($cartItem);
            $this->entityManager->flush();
        }
    
        return $this->redirectToRoute('cart');
    }
    

    #[Route('/increase-quantity/{id}', name: 'increase_quantity')]
    public function increaseQuantity(int $id): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }
    
        $cartItem = $this->entityManager
            ->getRepository(Cart::class)
            ->find($id);
    
        if ($cartItem && $cartItem->getUser() === $user) {
            $product = $cartItem->getProduct();
    
            // Vérifier le stock disponible
            if ($product->getStock() > 0) {
                $cartItem->setQuantity($cartItem->getQuantity() + 1);
                // Déduire du stock
                $product->setStock($product->getStock() - 1);
                $this->entityManager->flush();
            } else {
                $this->addFlash('error', 'Ce produit est en rupture de stock');
            }
        }
    
        return $this->redirectToRoute('cart');
    }
    
    #[Route('/decrease-quantity/{id}', name: 'decrease_quantity')]
    public function decreaseQuantity(int $id): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }
    
        $cartItem = $this->entityManager
            ->getRepository(Cart::class)
            ->find($id);
    
        if ($cartItem && $cartItem->getUser() === $user) {
            $product = $cartItem->getProduct();
    
            // Vérifier si la quantité est supérieure à 1
            if ($cartItem->getQuantity() > 1) {
                $cartItem->setQuantity($cartItem->getQuantity() - 1);
                // Restaurer le stock
                $product->setStock($product->getStock() + 1);
            } else {
                // Supprimer l'élément du panier et restaurer le stock
                $product->setStock($product->getStock() + 1);
                $this->entityManager->remove($cartItem);
            }
            $this->entityManager->flush();
        }
    
        return $this->redirectToRoute('cart');
    }
    
}
