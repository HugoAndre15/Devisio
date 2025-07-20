<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
#[IsGranted('ROLE_USER')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_products', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->setParameter('company', $company)
            ->orderBy('p.name', 'ASC');

        // Filtrage par type si demandé
        $type = $request->query->get('type');
        if ($type && in_array($type, array_keys(Product::getTypeChoices()))) {
            $queryBuilder->andWhere('p.type = :type')
                        ->setParameter('type', $type);
        }

        // Filtrage par statut actif/inactif
        $status = $request->query->get('status');
        if ($status !== null) {
            $isActive = $status === 'active';
            $queryBuilder->andWhere('p.isActive = :isActive')
                        ->setParameter('isActive', $isActive);
        }

        // Recherche par texte
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('p.name LIKE :search OR p.description LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $products = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques pour la vue
        $stats = [
            'total' => $productRepository->countByCompany($company),
            'active' => $productRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->andWhere('p.company = :company')
                ->andWhere('p.isActive = :active')
                ->setParameter('company', $company)
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'by_type' => $productRepository->getProductsByCategory($company),
        ];

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'stats' => $stats,
            'current_type' => $type,
            'current_status' => $status,
            'current_search' => $search,
            'product_types' => Product::getTypeChoices(),
        ]);
    }

    #[Route('/new', name: 'app_products_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $product->setCompany($this->getUser()->getCompany());
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été créé avec succès.');
            
            // Redirection selon le bouton cliqué
            $submitAction = $request->request->get('submit_action');
            if ($submitAction === 'save_and_new') {
                return $this->redirectToRoute('app_products_new');
            }
            
            return $this->redirectToRoute('app_products_show', ['id' => $product->getId()]);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_products_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        $this->denyAccessUnlessGranted('view', $product);

        // Statistiques d'utilisation du produit
        $quoteItems = $product->getQuoteItems();
        $invoiceItems = $product->getInvoiceItems();

        $stats = [
            'quotes_count' => $quoteItems->count(),
            'invoices_count' => $invoiceItems->count(),
            'total_quoted' => 0,
            'total_invoiced' => 0,
        ];

        // Calculer les montants totaux
        foreach ($quoteItems as $item) {
            $stats['total_quoted'] += (float) $item->getTotal();
        }

        foreach ($invoiceItems as $item) {
            $stats['total_invoiced'] += (float) $item->getTotal();
        }

        // Récupérer les utilisations récentes
        $recentQuotes = [];
        foreach ($quoteItems as $item) {
            $recentQuotes[] = $item->getQuote();
        }
        usort($recentQuotes, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        $recentQuotes = array_slice($recentQuotes, 0, 5);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'stats' => $stats,
            'recent_quotes' => $recentQuotes,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_products_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $product);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été modifié avec succès.');
            return $this->redirectToRoute('app_products_show', ['id' => $product->getId()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_products_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $product);

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            // Vérifier qu'il n'y a pas de devis ou factures associés
            if ($product->getQuoteItems()->count() > 0 || $product->getInvoiceItems()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce produit car il est utilisé dans des devis ou factures.');
                return $this->redirectToRoute('app_products_show', ['id' => $product->getId()]);
            }

            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Le produit a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_products');
    }

    #[Route('/{id}/toggle-status', name: 'app_products_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function toggleStatus(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $product);

        if ($this->isCsrfTokenValid('toggle_status'.$product->getId(), $request->request->get('_token'))) {
            $product->setIsActive(!$product->isActive());
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $status = $product->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Le produit a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_products_show', ['id' => $product->getId()]);
    }

    #[Route('/search', name: 'app_products_search', methods: ['GET'])]
    public function search(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $company = $this->getUser()->getCompany();

        if (strlen($query) < 2) {
            return new JsonResponse(['products' => []]);
        }

        $products = $productRepository->searchByCompany($company, $query);

        $results = array_map(function ($product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'type' => $product->getTypeLabel(),
                'price' => $product->getPrice(),
                'unit' => $product->getUnit(),
            ];
        }, array_slice($products, 0, 10)); // Limiter à 10 résultats

        return new JsonResponse(['products' => $results]);
    }

    #[Route('/export', name: 'app_products_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function export(ProductRepository $productRepository): Response
    {
        $company = $this->getUser()->getCompany();
        $products = $productRepository->findByCompany($company);
        
        return $this->exportToCsv($products);
    }

    #[Route('/stats', name: 'app_products_stats', methods: ['GET'])]
    public function stats(ProductRepository $productRepository): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        
        $stats = [
            'total' => $productRepository->countByCompany($company),
            'active' => $productRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->andWhere('p.company = :company')
                ->andWhere('p.isActive = :active')
                ->setParameter('company', $company)
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'by_category' => $productRepository->getProductsByCategory($company),
            'most_used' => $productRepository->getMostUsedProducts($company, 5),
        ];
        
        return new JsonResponse($stats);
    }

    #[Route('/bulk-action', name: 'app_products_bulk_action', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bulkAction(Request $request, ProductRepository $productRepository, EntityManagerInterface $entityManager): Response
    {
        $action = $request->request->get('action');
        $productIds = $request->request->get('product_ids', []);
        $company = $this->getUser()->getCompany();

        if (empty($productIds) || !in_array($action, ['activate', 'deactivate', 'delete'])) {
            $this->addFlash('error', 'Action invalide ou aucun produit sélectionné.');
            return $this->redirectToRoute('app_products');
        }

        $products = $productRepository->findBy([
            'id' => $productIds,
            'company' => $company
        ]);

        $count = 0;
        foreach ($products as $product) {
            switch ($action) {
                case 'activate':
                    $product->setIsActive(true);
                    $product->setUpdatedAt(new \DateTimeImmutable());
                    $count++;
                    break;
                case 'deactivate':
                    $product->setIsActive(false);
                    $product->setUpdatedAt(new \DateTimeImmutable());
                    $count++;
                    break;
                case 'delete':
                    if ($product->getQuoteItems()->count() === 0 && $product->getInvoiceItems()->count() === 0) {
                        $entityManager->remove($product);
                        $count++;
                    }
                    break;
            }
        }

        $entityManager->flush();

        $actionLabel = match($action) {
            'activate' => 'activés',
            'deactivate' => 'désactivés',
            'delete' => 'supprimés',
        };

        $this->addFlash('success', "{$count} produits {$actionLabel} avec succès.");
        return $this->redirectToRoute('app_products');
    }

    private function exportToCsv(array $products): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="produits-export-' . date('Y-m-d') . '.csv"');
        
        $handle = fopen('php://temp', 'r+');
        
        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Headers CSV
        fputcsv($handle, [
            'Nom',
            'Description',
            'Type',
            'Prix',
            'Unité',
            'Statut',
            'Créé le',
            'Modifié le'
        ], ';');
        
        // Données
        foreach ($products as $product) {
            fputcsv($handle, [
                $product->getName(),
                $product->getDescription() ?: '',
                $product->getTypeLabel(),
                $product->getPrice(),
                $product->getUnit(),
                $product->isActive() ? 'Actif' : 'Inactif',
                $product->getCreatedAt()->format('d/m/Y H:i'),
                $product->getUpdatedAt()->format('d/m/Y H:i')
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        $response->setContent($content);
        
        return $response;
    }
}