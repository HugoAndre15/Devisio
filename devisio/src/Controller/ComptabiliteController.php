<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\QuoteRepository;
use App\Repository\InvoiceRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;

class ComptabiliteController extends AbstractController
{
    #[Route('/comptabilite', name: 'app_comptabilite')]
    public function index(
        QuoteRepository $quoteRepository,
        InvoiceRepository $invoiceRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository
    ): Response
    {
        $user = $this->getUser();
        $company = $user->getCompany();

        // Statistics for the dashboard
        $stats = [
            'quotes' => [
                'total' => $quoteRepository->countByCompany($company),
            ],
            'invoices' => [
                'total' => $invoiceRepository->countByCompany($company),
                'draft' => $invoiceRepository->countByCompanyAndStatus($company, 'draft'),
                'sent' => $invoiceRepository->countByCompanyAndStatus($company, 'sent'),
                'paid' => $invoiceRepository->countByCompanyAndStatus($company, 'paid'),
                'overdue' => $invoiceRepository->countByCompanyAndStatus($company, 'overdue'),
            ],
        ];

        // Monthly revenue
        $monthlyRevenue = $invoiceRepository->getMonthlyRevenueByCompany($company, 12);
        $recentInvoices = $invoiceRepository->findRecentByCompany($company, 5);

        return $this->render('comptabilite/index.html.twig', [
            'stats' => $stats,
            'recent_invoices' => $recentInvoices,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }

    #[Route('/comptabilite/pdf', name: 'app_comptabilite_pdf', methods: ['GET'])]
    public function pdf(
        QuoteRepository $quoteRepository,
        InvoiceRepository $invoiceRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        \App\Service\PdfGeneratorService $pdfGenerator
    ): Response
    {
        $user = $this->getUser();
        $company = $user->getCompany();

        $stats = [
            'quotes' => [
                'total' => $quoteRepository->countByCompany($company),
            ],
            'invoices' => [
                'total' => $invoiceRepository->countByCompany($company),
                'draft' => $invoiceRepository->countByCompanyAndStatus($company, 'draft'),
                'sent' => $invoiceRepository->countByCompanyAndStatus($company, 'sent'),
                'paid' => $invoiceRepository->countByCompanyAndStatus($company, 'paid'),
                'overdue' => $invoiceRepository->countByCompanyAndStatus($company, 'overdue'),
            ],
        ];
        $monthlyRevenue = $invoiceRepository->getMonthlyRevenueByCompany($company, 12);
        $recentInvoices = $invoiceRepository->findRecentByCompany($company, 5);

        $html = $this->renderView('comptabilite/pdf.html.twig', [
            'stats' => $stats,
            'recent_invoices' => $recentInvoices,
            'monthly_revenue' => $monthlyRevenue,
        ]);
        $pdf = $pdfGenerator->generateComptaPdf($html);

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="rapport-comptabilite.pdf"'
            ]
        );
    }
}
