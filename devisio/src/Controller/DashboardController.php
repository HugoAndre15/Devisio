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
            ],
            'invoices' => [
                'total' => $invoiceRepository->countByCompany($company),
                'draft' => $invoiceRepository->countByCompanyAndStatus($company, 'draft'),
                'sent' => $invoiceRepository->countByCompanyAndStatus($company, 'sent'),
                'paid' => $invoiceRepository->countByCompanyAndStatus($company, 'paid'),
                'overdue' => $invoiceRepository->countByCompanyAndStatus($company, 'overdue'),
            ],
            'customers' => $customerRepository->countByCompany($company),
            'products' => $productRepository->countByCompany($company),
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