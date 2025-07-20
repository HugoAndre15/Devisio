<?php

namespace App\Controller;

use App\Repository\QuoteRepository;
use App\Repository\InvoiceRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard_alt')]
    public function index(
        QuoteRepository $quoteRepository,
        InvoiceRepository $invoiceRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository
    ): Response {
        $user = $this->getUser();
        $company = $user->getCompany();

        // Statistics for the dashboard
        $stats = [
            'quotes' => [
                'total' => $quoteRepository->countByCompany($company),
                'draft' => $quoteRepository->countByCompanyAndStatus($company, 'draft'),
                'sent' => $quoteRepository->countByCompanyAndStatus($company, 'sent'),
                'accepted' => $quoteRepository->countByCompanyAndStatus($company, 'accepted'),
                'rejected' => $quoteRepository->countByCompanyAndStatus($company, 'rejected'),
            ],
            'invoices' => [
                'total' => $invoiceRepository->countByCompany($company),
                'draft' => $invoiceRepository->countByCompanyAndStatus($company, 'draft'),
                'sent' => $invoiceRepository->countByCompanyAndStatus($company, 'sent'),
                'paid' => $invoiceRepository->countByCompanyAndStatus($company, 'paid'),
                'overdue' => $invoiceRepository->countByCompanyAndStatus($company, 'overdue'),
            ],
            'customers' => [
                'total' => $customerRepository->countByCompany($company),
                'individuals' => $customerRepository->createQueryBuilder('c')
                    ->select('COUNT(c.id)')
                    ->andWhere('c.company = :company')
                    ->andWhere('c.type = :type')
                    ->setParameter('company', $company)
                    ->setParameter('type', 'individual')
                    ->getQuery()
                    ->getSingleScalarResult(),
                'companies' => $customerRepository->createQueryBuilder('c')
                    ->select('COUNT(c.id)')
                    ->andWhere('c.company = :company')
                    ->andWhere('c.type = :type')
                    ->setParameter('company', $company)
                    ->setParameter('type', 'company')
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
            ],
            'products' => [
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
                'services' => $productRepository->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->andWhere('p.company = :company')
                    ->andWhere('p.type IN (:serviceTypes)')
                    ->setParameter('company', $company)
                    ->setParameter('serviceTypes', ['activity', 'guide', 'other'])
                    ->getQuery()
                    ->getSingleScalarResult(),
                'products' => $productRepository->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->andWhere('p.company = :company')
                    ->andWhere('p.type IN (:productTypes)')
                    ->setParameter('company', $company)
                    ->setParameter('productTypes', ['accommodation', 'transport', 'package', 'insurance', 'meal'])
                    ->getQuery()
                    ->getSingleScalarResult(),
            ],
        ];

        // Recent activity
        $recentQuotes = $quoteRepository->findRecentByCompany($company, 5);
        $recentInvoices = $invoiceRepository->findRecentByCompany($company, 5);
        $overdueInvoices = $invoiceRepository->findOverdueByCompany($company);

        // Monthly revenue
        $monthlyRevenue = $invoiceRepository->getMonthlyRevenueByCompany($company, 12);

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'recent_quotes' => $recentQuotes,
            'recent_invoices' => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }
}