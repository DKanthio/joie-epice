<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifiez si l'utilisateur est connecté et s'il a le rôle administrateur
        if (!$this->getUser() || !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            throw $this->createAccessDeniedException('Accès refusé. Veuillez vous connecter en tant qu\'administrateur.');
        }

        // Gestion de l'ajout de produit
        if ($request->isMethod('POST') && $request->request->has('ajouter')) {
            $product = new Product();
            $product->setName($request->request->get('name'));
            $product->setPrice($request->request->get('price'));
            $product->setImage($request->request->get('image'));
            $product->setStock(2); // ou autre logique pour le stock initial
            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        // Gestion de la suppression de produit
        if ($request->isMethod('POST') && $request->request->has('supprimer')) {
            $productId = $request->request->get('productId');
            $product = $entityManager->getRepository(Product::class)->find($productId);
            if ($product) {
                $entityManager->remove($product);
                $entityManager->flush();
            }
            return $this->redirectToRoute('admin_dashboard');
        }

        // Gestion de la modification de produit
        if ($request->isMethod('POST') && $request->request->has('modifier')) {
            $productId = $request->request->get('productId');
            $product = $entityManager->getRepository(Product::class)->find($productId);
            if ($product) {
                $product->setName($request->request->get('name'));
                $product->setPrice($request->request->get('price'));
                $product->setImage($request->request->get('image'));
                $entityManager->flush();
            }
            return $this->redirectToRoute('admin_dashboard');
        }

        // Récupération des produits
        $products = $entityManager->getRepository(Product::class)->findAll();
        
        return $this->render('admin/dashboard.html.twig', [
            'products' => $products,
        ]);
    }
}
