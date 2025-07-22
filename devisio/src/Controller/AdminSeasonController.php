<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\ProductPrice;
use App\Form\SeasonType;
use App\Repository\SeasonRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/seasons')]
#[IsGranted('ROLE_ADMIN')]
class AdminSeasonController extends AbstractController
{
    #[Route('/', name: 'app_admin_seasons', methods: ['GET'])]
    public function index(Request $request, SeasonRepository $seasonRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $seasonRepository->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->setParameter('company', $company)
            ->orderBy('s.startDate', 'ASC');

        // Filtrage par statut actif/inactif
        $status = $request->query->get('status');
        if ($status !== null) {
            $isActive = $status === 'active';
            $queryBuilder->andWhere('s.isActive = :isActive')
                        ->setParameter('isActive', $isActive);
        }

        // Recherche par nom
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('s.name LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $seasons = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques
        $stats = [
            'total' => $seasonRepository->countByCompany($company),
            'active' => $seasonRepository->countActiveByCompany($company),
            'current' => $seasonRepository->findCurrentSeason($company),
        ];

        return $this->render('admin/seasons/index.html.twig', [
            'seasons' => $seasons,
            'stats' => $stats,
            'current_status' => $status,
            'current_search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_seasons_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $season = new Season();
        $season->setCompany($this->getUser()->getCompany());
        
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier qu'il n'y a pas de chevauchement de dates
            if ($this->hasDateOverlap($season, $entityManager)) {
                $this->addFlash('error', 'Cette période chevauche avec une saison existante.');
                return $this->render('admin/seasons/new.html.twig', [
                    'season' => $season,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($season);
            $entityManager->flush();

            $this->addFlash('success', 'La saison a été créée avec succès.');
            return $this->redirectToRoute('app_admin_seasons_show', ['id' => $season->getId()]);
        }

        return $this->render('admin/seasons/new.html.twig', [
            'season' => $season,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_seasons_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Season $season, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('view', $season);

        // Récupérer les produits avec tarifs pour cette saison
        $productsWithPrices = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.prices', 'pp')
            ->leftJoin('pp.season', 's')
            ->addSelect('pp', 's')
            ->andWhere('p.company = :company')
            ->andWhere('pp.season = :season OR pp.season IS NULL')
            ->setParameter('company', $this->getUser()->getCompany())
            ->setParameter('season', $season)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Statistiques de la saison
        $stats = [
            'products_with_special_price' => count(array_filter($productsWithPrices, fn($p) => $p->getPrices()->count() > 0)),
            'total_products' => count($productsWithPrices),
            'is_current' => $season->isActiveForDate(new \DateTime()),
            'days_remaining' => $season->getDaysUntilEnd(),
        ];

        return $this->render('admin/seasons/show.html.twig', [
            'season' => $season,
            'products' => $productsWithPrices,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_seasons_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $season);

        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier qu'il n'y a pas de chevauchement de dates (sauf avec soi-même)
            if ($this->hasDateOverlap($season, $entityManager, $season->getId())) {
                $this->addFlash('error', 'Cette période chevauche avec une autre saison existante.');
                return $this->render('admin/seasons/edit.html.twig', [
                    'season' => $season,
                    'form' => $form,
                ]);
            }

            $season->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'La saison a été modifiée avec succès.');
            return $this->redirectToRoute('app_admin_seasons_show', ['id' => $season->getId()]);
        }

        return $this->render('admin/seasons/edit.html.twig', [
            'season' => $season,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_seasons_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $season);

        if ($this->isCsrfTokenValid('delete'.$season->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des tarifs associés
            if ($season->getProductPrices()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette saison car elle a des tarifs associés.');
                return $this->redirectToRoute('app_admin_seasons_show', ['id' => $season->getId()]);
            }

            $entityManager->remove($season);
            $entityManager->flush();
            $this->addFlash('success', 'La saison a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_seasons');
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_seasons_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $season);

        if ($this->isCsrfTokenValid('toggle_status'.$season->getId(), $request->request->get('_token'))) {
            $season->setIsActive(!$season->isActive());
            $season->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $status = $season->isActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', "La saison a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_admin_seasons_show', ['id' => $season->getId()]);
    }

    #[Route('/{id}/set-prices', name: 'app_admin_seasons_set_prices', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function setPrices(Request $request, Season $season, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('edit', $season);

        if (!$this->isCsrfTokenValid('set_prices'.$season->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_seasons_show', ['id' => $season->getId()]);
        }

        $prices = $request->request->get('prices', []);
        $company = $this->getUser()->getCompany();
        
        $updatedCount = 0;

        foreach ($prices as $productId => $priceData) {
            $product = $productRepository->find($productId);
            
            if (!$product || $product->getCompany() !== $company) {
                continue;
            }

            // Chercher un prix existant pour ce produit et cette saison
            $existingPrice = $entityManager->getRepository(ProductPrice::class)->findOneBy([
                'product' => $product,
                'season' => $season
            ]);

            $newPrice = (float) $priceData['price'];
            
            if ($newPrice > 0) {
                if ($existingPrice) {
                    // Modifier le prix existant
                    $existingPrice->setPrice($newPrice);
                    $existingPrice->setUpdatedAt(new \DateTimeImmutable());
                } else {
                    // Créer un nouveau prix
                    $productPrice = new ProductPrice();
                    $productPrice->setProduct($product);
                    $productPrice->setSeason($season);
                    $productPrice->setPrice($newPrice);
                    $entityManager->persist($productPrice);
                }
                $updatedCount++;
            } elseif ($existingPrice) {
                // Supprimer le prix si mis à 0
                $entityManager->remove($existingPrice);
            }
        }

        $entityManager->flush();

        $this->addFlash('success', "{$updatedCount} tarifs ont été mis à jour pour la saison {$season->getName()}.");
        return $this->redirectToRoute('app_admin_seasons_show', ['id' => $season->getId()]);
    }

    #[Route('/current', name: 'app_admin_seasons_current', methods: ['GET'])]
    public function getCurrentSeason(SeasonRepository $seasonRepository): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        $currentSeason = $seasonRepository->findCurrentSeason($company);

        if (!$currentSeason) {
            return new JsonResponse(['season' => null]);
        }

        return new JsonResponse([
            'season' => [
                'id' => $currentSeason->getId(),
                'name' => $currentSeason->getName(),
                'multiplier' => (float) $currentSeason->getMultiplier(),
                'startDate' => $currentSeason->getStartDate()->format('Y-m-d'),
                'endDate' => $currentSeason->getEndDate()->format('Y-m-d'),
            ]
        ]);
    }

    private function hasDateOverlap(Season $season, EntityManagerInterface $entityManager, ?int $excludeId = null): bool
    {
        $qb = $entityManager->createQueryBuilder()
            ->select('s')
            ->from(Season::class, 's')
            ->andWhere('s.company = :company')
            ->andWhere('s.isActive = :active')
            ->andWhere(
                '(s.startDate <= :endDate AND s.endDate >= :startDate)'
            )
            ->setParameter('company', $season->getCompany())
            ->setParameter('active', true)
            ->setParameter('startDate', $season->getStartDate())
            ->setParameter('endDate', $season->getEndDate());

        if ($excludeId) {
            $qb->andWhere('s.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return count($qb->getQuery()->getResult()) > 0;
    }
}