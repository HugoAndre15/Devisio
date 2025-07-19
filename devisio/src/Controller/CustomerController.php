<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customers')]
#[IsGranted('ROLE_USER')]
class CustomerController extends AbstractController
{
    #[Route('/', name: 'app_customers', methods: ['GET'])]
    public function index(Request $request, CustomerRepository $customerRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $customerRepository->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC');

        // Filtrage par type si demandé
        $type = $request->query->get('type');
        if ($type && in_array($type, [Customer::TYPE_INDIVIDUAL, Customer::TYPE_COMPANY])) {
            $queryBuilder->andWhere('c.type = :type')
                        ->setParameter('type', $type);
        }

        // Filtrage par statut actif/inactif
        $status = $request->query->get('status');
        if ($status !== null) {
            $isActive = $status === 'active';
            $queryBuilder->andWhere('c.isActive = :isActive')
                        ->setParameter('isActive', $isActive);
        }

        // Recherche par texte
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search OR c.companyName LIKE :search OR c.phone LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $customers = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques pour la vue
        $stats = [
            'total' => $customerRepository->countByCompany($company),
            'individuals' => $customerRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.company = :company')
                ->andWhere('c.type = :type')
                ->setParameter('company', $company)
                ->setParameter('type', Customer::TYPE_INDIVIDUAL)
                ->getQuery()
                ->getSingleScalarResult(),
            'companies' => $customerRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.company = :company')
                ->andWhere('c.type = :type')
                ->setParameter('company', $company)
                ->setParameter('type', Customer::TYPE_COMPANY)
                ->getQuery()
                ->getSingleScalarResult(),
            'active' => $customerRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.company = :company')
                ->andWhere('c.isActive = :active')
                ->setParameter('company', $company)
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        return $this->render('customer/index.html.twig', [
            'customers' => $customers,
            'stats' => $stats,
            'current_type' => $type,
            'current_status' => $status,
            'current_search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_customers_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customer = new Customer();
        $customer->setCompany($this->getUser()->getCompany());
        
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($customer);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été créé avec succès.');
            
            // Redirection selon le bouton cliqué
            $submitAction = $request->request->get('submit_action');
            if ($submitAction === 'save_and_new') {
                return $this->redirectToRoute('app_customers_new');
            }
            
            return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Customer $customer): Response
    {
        $this->denyAccessUnlessGranted('view', $customer);

        // Récupérer les devis et factures du client
        $quotes = $customer->getQuotes()->toArray();
        $invoices = $customer->getInvoices()->toArray();

        // Trier par date de création décroissante
        usort($quotes, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        usort($invoices, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        // Statistiques du client
        $stats = [
            'quotes_count' => count($quotes),
            'quotes_total' => array_sum(array_map(fn($q) => (float)$q->getTotal(), $quotes)),
            'invoices_count' => count($invoices),
            'invoices_total' => array_sum(array_map(fn($i) => (float)$i->getTotal(), $invoices)),
            'invoices_paid' => array_sum(array_map(fn($i) => $i->getStatus() === 'paid' ? (float)$i->getTotal() : 0, $invoices)),
        ];

        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
            'quotes' => array_slice($quotes, 0, 10), // Limiter à 10 pour l'affichage
            'invoices' => array_slice($invoices, 0, 10),
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customers_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $customer);

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été modifié avec succès.');
            return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_customers_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $customer);

        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->request->get('_token'))) {
            // Vérifier qu'il n'y a pas de devis ou factures associés
            if ($customer->getQuotes()->count() > 0 || $customer->getInvoices()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce client car il a des devis ou factures associés.');
                return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
            }

            $entityManager->remove($customer);
            $entityManager->flush();
            $this->addFlash('success', 'Le client a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_customers');
    }

    #[Route('/{id}/toggle-status', name: 'app_customers_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $customer);

        if ($this->isCsrfTokenValid('toggle_status'.$customer->getId(), $request->request->get('_token'))) {
            $customer->setIsActive(!$customer->isActive());
            $customer->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $status = $customer->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Le client a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
    }

    #[Route('/search', name: 'app_customers_search', methods: ['GET'])]
    public function search(Request $request, CustomerRepository $customerRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $company = $this->getUser()->getCompany();

        if (strlen($query) < 2) {
            return new JsonResponse(['customers' => []]);
        }

        $customers = $customerRepository->searchByCompany($company, $query);

        $results = array_map(function ($customer) {
            return [
                'id' => $customer->getId(),
                'name' => $customer->getDisplayName(),
                'email' => $customer->getEmail(),
                'type' => $customer->getTypeLabel(),
                'phone' => $customer->getPhone(),
            ];
        }, array_slice($customers, 0, 10)); // Limiter à 10 résultats

        return new JsonResponse(['customers' => $results]);
    }

    #[Route('/export', name: 'app_customers_export', methods: ['GET'])]
    public function export(CustomerRepository $customerRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $company = $this->getUser()->getCompany();
        $customers = $customerRepository->findByCompany($company);
        
        return $this->exportToCsv($customers);
    }

    #[Route('/stats', name: 'app_customers_stats', methods: ['GET'])]
    public function stats(CustomerRepository $customerRepository): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        
        $stats = [
            'total' => $customerRepository->countByCompany($company),
            'individuals' => $customerRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.company = :company')
                ->andWhere('c.type = :type')
                ->setParameter('company', $company)
                ->setParameter('type', Customer::TYPE_INDIVIDUAL)
                ->getQuery()
                ->getSingleScalarResult(),
            'companies' => $customerRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.company = :company')
                ->andWhere('c.type = :type')
                ->setParameter('company', $company)
                ->setParameter('type', Customer::TYPE_COMPANY)
                ->getQuery()
                ->getSingleScalarResult(),
            'active' => $customerRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.company = :company')
                ->andWhere('c.isActive = :active')
                ->setParameter('company', $company)
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'top_customers' => $customerRepository->getTopCustomersByRevenue($company, 5),
        ];
        
        return new JsonResponse($stats);
    }

    private function exportToCsv(array $customers): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="clients-export-' . date('Y-m-d') . '.csv"');
        
        $handle = fopen('php://temp', 'r+');
        
        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Headers CSV
        fputcsv($handle, [
            'Type',
            'Nom',
            'Prénom',
            'Nom société',
            'Email',
            'Téléphone',
            'Adresse',
            'Code postal',
            'Ville',
            'Pays',
            'SIRET',
            'N° TVA',
            'Statut',
            'Créé le',
            'Modifié le'
        ], ';');
        
        // Données
        foreach ($customers as $customer) {
            fputcsv($handle, [
                $customer->getTypeLabel(),
                $customer->getLastName(),
                $customer->getFirstName(),
                $customer->getCompanyName() ?: '',
                $customer->getEmail(),
                $customer->getPhone() ?: '',
                $customer->getAddress(),
                $customer->getPostalCode(),
                $customer->getCity(),
                $customer->getCountry(),
                $customer->getSiret() ?: '',
                $customer->getVatNumber() ?: '',
                $customer->isActive() ? 'Actif' : 'Inactif',
                $customer->getCreatedAt()->format('d/m/Y H:i'),
                $customer->getUpdatedAt()->format('d/m/Y H:i')
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        $response->setContent($content);
        
        return $response;
    }
}