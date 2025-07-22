<?php

namespace App\Controller;

use App\Entity\DiscountCode;
use App\Form\DiscountCodeType;
use App\Repository\DiscountCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/discount-codes')]
#[IsGranted('ROLE_ADMIN')]
class AdminDiscountCodeController extends AbstractController
{
    #[Route('/', name: 'app_admin_discount_codes', methods: ['GET'])]
    public function index(Request $request, DiscountCodeRepository $discountCodeRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $discountCodeRepository->createQueryBuilder('d')
            ->andWhere('d.company = :company')
            ->setParameter('company', $company)
            ->orderBy('d.createdAt', 'DESC');

        // Filtrage par statut
        $status = $request->query->get('status');
        if ($status !== null) {
            $isActive = $status === 'active';
            $queryBuilder->andWhere('d.isActive = :isActive')
                        ->setParameter('isActive', $isActive);
        }

        // Filtrage par type
        $type = $request->query->get('type');
        if ($type && in_array($type, [DiscountCode::TYPE_PERCENTAGE, DiscountCode::TYPE_FIXED_AMOUNT])) {
            $queryBuilder->andWhere('d.type = :type')
                        ->setParameter('type', $type);
        }

        // Recherche par code ou nom
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('d.code LIKE :search OR d.name LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $discountCodes = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques
        $stats = [
            'total' => $discountCodeRepository->countByCompany($company),
            'active' => $discountCodeRepository->countActiveByCompany($company),
            'expired' => $discountCodeRepository->countExpiredByCompany($company),
            'used_this_month' => $discountCodeRepository->countUsedThisMonth($company),
        ];

        return $this->render('admin/discount-codes/index.html.twig', [
            'discount_codes' => $discountCodes,
            'stats' => $stats,
            'current_status' => $status,
            'current_type' => $type,
            'current_search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_discount_codes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $discountCode = new DiscountCode();
        $discountCode->setCompany($this->getUser()->getCompany());
        
        $form = $this->createForm(DiscountCodeType::class, $discountCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code
            $existingCode = $entityManager->getRepository(DiscountCode::class)->findOneBy([
                'code' => $discountCode->getCode(),
                'company' => $this->getUser()->getCompany()
            ]);

            if ($existingCode) {
                $this->addFlash('error', 'Un code de réduction avec ce code existe déjà.');
                return $this->render('admin/discount-codes/new.html.twig', [
                    'discount_code' => $discountCode,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($discountCode);
            $entityManager->flush();

            $this->addFlash('success', 'Le code de réduction a été créé avec succès.');
            return $this->redirectToRoute('app_admin_discount_codes_show', ['id' => $discountCode->getId()]);
        }

        return $this->render('admin/discount-codes/new.html.twig', [
            'discount_code' => $discountCode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_discount_codes_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(DiscountCode $discountCode): Response
    {
        $this->denyAccessUnlessGranted('view', $discountCode);

        // Statistiques d'utilisation
        $stats = [
            'usage_count' => $discountCode->getUsageCount(),
            'usage_limit' => $discountCode->getUsageLimit(),
            'quotes_count' => $discountCode->getQuotes()->count(),
            'invoices_count' => $discountCode->getInvoices()->count(),
            'total_savings' => 0,
            'can_be_used' => $discountCode->canBeUsed(),
            'days_until_expiration' => $discountCode->getDaysUntilExpiration(),
        ];

        // Calculer les économies totales
        foreach ($discountCode->getQuotes() as $quote) {
            if ($quote->getDiscountAmount()) {
                $stats['total_savings'] += (float) $quote->getDiscountAmount();
            }
        }

        foreach ($discountCode->getInvoices() as $invoice) {
            if ($invoice->getDiscountAmount()) {
                $stats['total_savings'] += (float) $invoice->getDiscountAmount();
            }
        }

        return $this->render('admin/discount-codes/show.html.twig', [
            'discount_code' => $discountCode,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_discount_codes_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, DiscountCode $discountCode, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $discountCode);

        $form = $this->createForm(DiscountCodeType::class, $discountCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code (sauf pour soi-même)
            $existingCode = $entityManager->getRepository(DiscountCode::class)->findOneBy([
                'code' => $discountCode->getCode(),
                'company' => $this->getUser()->getCompany()
            ]);

            if ($existingCode && $existingCode->getId() !== $discountCode->getId()) {
                $this->addFlash('error', 'Un autre code de réduction avec ce code existe déjà.');
                return $this->render('admin/discount-codes/edit.html.twig', [
                    'discount_code' => $discountCode,
                    'form' => $form,
                ]);
            }

            $discountCode->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Le code de réduction a été modifié avec succès.');
            return $this->redirectToRoute('app_admin_discount_codes_show', ['id' => $discountCode->getId()]);
        }

        return $this->render('admin/discount-codes/edit.html.twig', [
            'discount_code' => $discountCode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_discount_codes_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, DiscountCode $discountCode, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $discountCode);

        if ($this->isCsrfTokenValid('delete'.$discountCode->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des devis/factures associés
            if ($discountCode->getQuotes()->count() > 0 || $discountCode->getInvoices()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce code car il est utilisé dans des devis ou factures.');
                return $this->redirectToRoute('app_admin_discount_codes_show', ['id' => $discountCode->getId()]);
            }

            $entityManager->remove($discountCode);
            $entityManager->flush();
            $this->addFlash('success', 'Le code de réduction a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_discount_codes');
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_discount_codes_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, DiscountCode $discountCode, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $discountCode);

        if ($this->isCsrfTokenValid('toggle_status'.$discountCode->getId(), $request->request->get('_token'))) {
            $discountCode->setIsActive(!$discountCode->isActive());
            $discountCode->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $status = $discountCode->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Le code de réduction a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_admin_discount_codes_show', ['id' => $discountCode->getId()]);
    }

    #[Route('/validate', name: 'app_admin_discount_codes_validate', methods: ['POST'])]
    public function validateCode(Request $request, DiscountCodeRepository $discountCodeRepository): JsonResponse
    {
        $code = $request->request->get('code');
        $amount = (float) $request->request->get('amount', 0);
        $company = $this->getUser()->getCompany();

        if (!$code) {
            return new JsonResponse(['valid' => false, 'message' => 'Code requis']);
        }

        $discountCode = $discountCodeRepository->findValidCodeByCompany($company, $code);

        if (!$discountCode) {
            return new JsonResponse(['valid' => false, 'message' => 'Code invalide ou expiré']);
        }

        if (!$discountCode->canBeUsed()) {
            return new JsonResponse(['valid' => false, 'message' => 'Code non utilisable']);
        }

        if (!$discountCode->isValidForAmount($amount)) {
            $minimumAmount = number_format((float) $discountCode->getMinimumAmount(), 2, ',', ' ');
            return new JsonResponse(['valid' => false, 'message' => "Montant minimum requis: {$minimumAmount} €"]);
        }

        $discount = $discountCode->calculateDiscount($amount);

        return new JsonResponse([
            'valid' => true,
            'discount_code' => [
                'id' => $discountCode->getId(),
                'name' => $discountCode->getName(),
                'type' => $discountCode->getType(),
                'value' => $discountCode->getFormattedValue(),
                'discount_amount' => $discount,
                'formatted_discount' => number_format($discount, 2, ',', ' ') . ' €'
            ]
        ]);
    }

    #[Route('/generate-code', name: 'app_admin_discount_codes_generate', methods: ['POST'])]
    public function generateCode(): JsonResponse
    {
        // Générer un code aléatoire unique
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
            $exists = $this->getDoctrine()->getRepository(DiscountCode::class)->findOneBy([
                'code' => $code,
                'company' => $this->getUser()->getCompany()
            ]);
        } while ($exists);

        return new JsonResponse(['code' => $code]);
    }
}