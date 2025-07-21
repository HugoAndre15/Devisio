<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\InvoiceReminder;
use App\Entity\Quote;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use App\Repository\QuoteRepository;
use App\Service\PdfGeneratorService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invoices')]
#[IsGranted('ROLE_USER')]
class InvoiceController extends AbstractController
{
    #[Route('/', name: 'app_invoices', methods: ['GET'])]
    public function index(Request $request, InvoiceRepository $invoiceRepository, QuoteRepository $quoteRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $invoiceRepository->createQueryBuilder('i')
            ->andWhere('i.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->orderBy('i.createdAt', 'DESC');

        // Filtrage par statut si demandé
        $status = $request->query->get('status');
        if ($status && in_array($status, ['draft', 'sent', 'paid', 'overdue', 'cancelled'])) {
            $queryBuilder->andWhere('i.status = :status')
                        ->setParameter('status', $status);
        }

        // Recherche par texte
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('i.number LIKE :search OR i.subject LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search OR c.companyName LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $invoices = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques pour la vue
        $stats = [
            'total' => $invoiceRepository->countByCompany($company),
            'draft' => $invoiceRepository->countByCompanyAndStatus($company, 'draft'),
            'sent' => $invoiceRepository->countByCompanyAndStatus($company, 'sent'),
            'paid' => $invoiceRepository->countByCompanyAndStatus($company, 'paid'),
            'overdue' => $invoiceRepository->countByCompanyAndStatus($company, 'overdue'),
            'total_revenue' => $invoiceRepository->getTotalRevenueByCompany($company),
            'outstanding' => $invoiceRepository->getOutstandingAmountByCompany($company),
        ];

        // Récupérer les devis acceptés qui peuvent être facturés
        $acceptedQuotes = $quoteRepository->findAcceptedQuotesWithoutInvoice($company);

        return $this->render('invoice/index.html.twig', [
            'invoices' => $invoices,
            'stats' => $stats,
            'current_status' => $status,
            'current_search' => $search,
            'accepted_quotes' => $acceptedQuotes,
        ]);
    }

    #[Route('/create-from-quote/{id}', name: 'app_invoices_create_from_quote', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function createFromQuote(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le devis peut être converti
        if (!$quote->canBeConvertedToInvoice()) {
            $this->addFlash('error', 'Ce devis ne peut pas être converti en facture.');
            return $this->redirectToRoute('app_invoices');
        }

        // Vérifier les permissions
        $this->denyAccessUnlessGranted('convert', $quote);

        // NOUVEAUTÉ : Vérifier que le client du devis est toujours actif
        if (!$quote->getCustomer()->isActive()) {
            $this->addFlash('error', 'Impossible de créer une facture pour ce devis car le client est inactif. Réactivez d\'abord le client.');
            return $this->redirectToRoute('app_invoices');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('create_invoice_from_quote_' . $quote->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_invoices');
        }

        // Créer la facture à partir du devis automatiquement
        $invoice = new Invoice();
        $invoice->createFromQuote($quote);
        $invoice->setCreatedBy($this->getUser());

        // Pas de modification possible - création directe
        $entityManager->persist($invoice);
        $entityManager->flush();

        $this->addFlash('success', 'La facture ' . $invoice->getNumber() . ' a été créée avec succès depuis le devis ' . $quote->getNumber() . '.');

        return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
    }

    // Supprimer ou désactiver la méthode new() originale
    #[Route('/new', name: 'app_invoices_new', methods: ['GET'])]
    public function newRedirect(): Response
    {
        $this->addFlash('info', 'Pour créer une facture, vous devez d\'abord avoir un devis accepté. Sélectionnez un devis accepté ci-dessous.');
        return $this->redirectToRoute('app_invoices');
    }

    #[Route('/{id}', name: 'app_invoices_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Invoice $invoice): Response
    {
        $this->denyAccessUnlessGranted('view', $invoice);

        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_invoices_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $invoice);

        // Les factures créées depuis un devis ne peuvent pas être modifiées
        if ($invoice->getQuote()) {
            $this->addFlash('error', 'Cette facture a été créée depuis un devis et ne peut pas être modifiée. Les modifications doivent être faites sur le devis source.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        if (!$invoice->canBeModified()) {
            $this->addFlash('error', 'Cette facture ne peut plus être modifiée car elle a été envoyée.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Remove empty items
            foreach ($invoice->getItems() as $item) {
                if (!$item->getProductName()) {
                    $invoice->removeItem($item);
                }
            }

            if ($invoice->getItems()->isEmpty()) {
                $this->addFlash('error', 'La facture doit contenir au moins un article.');
                return $this->render('invoice/edit.html.twig', [
                    'invoice' => $invoice,
                    'form' => $form,
                ]);
            }

            $invoice->calculateTotals();
            $invoice->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            $this->addFlash('success', 'La facture a été modifiée avec succès.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
        ]);
    }

    // ... (le reste des méthodes reste inchangé)
    #[Route('/{id}/delete', name: 'app_invoices_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $invoice);

        if ($this->isCsrfTokenValid('delete'.$invoice->getId(), $request->request->get('_token'))) {
            // Vérifier qu'on peut supprimer
            if ($invoice->getStatus() !== Invoice::STATUS_DRAFT) {
                $this->addFlash('error', 'Seules les factures en brouillon peuvent être supprimées.');
                return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
            }

            $entityManager->remove($invoice);
            $entityManager->flush();
            $this->addFlash('success', 'La facture a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_invoices');
    }

    #[Route('/{id}/send', name: 'app_invoices_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(Request $request, Invoice $invoice, EmailService $emailService, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('send', $invoice);

        if (!$invoice->canBeSent()) {
            $this->addFlash('error', 'Cette facture ne peut pas être envoyée.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        if ($this->isCsrfTokenValid('send'.$invoice->getId(), $request->request->get('_token'))) {
            try {
                $emailService->sendInvoiceToCustomer($invoice);
                $invoice->markAsSent();
                $entityManager->flush();
                
                $this->addFlash('success', 'La facture a été envoyée avec succès à ' . $invoice->getCustomer()->getEmail());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi de la facture: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/mark-paid', name: 'app_invoices_mark_paid', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markPaid(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('manage', $invoice);

        if (!$invoice->canBePaid()) {
            $this->addFlash('error', 'Cette facture ne peut pas être marquée comme payée.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        if ($this->isCsrfTokenValid('mark_paid'.$invoice->getId(), $request->request->get('_token'))) {
            $invoice->markAsPaid();
            $entityManager->flush();
            
            $this->addFlash('success', 'La facture a été marquée comme payée.');
        }

        return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_invoices_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function cancel(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('cancel', $invoice);

        if (!$invoice->canBeCancelled()) {
            $this->addFlash('error', 'Cette facture ne peut pas être annulée.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        if ($this->isCsrfTokenValid('cancel'.$invoice->getId(), $request->request->get('_token'))) {
            $invoice->markAsCancelled();
            $entityManager->flush();
            
            $this->addFlash('success', 'La facture a été annulée.');
        }

        return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/pdf', name: 'app_invoices_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(Invoice $invoice, PdfGeneratorService $pdfGenerator): Response
    {
        $this->denyAccessUnlessGranted('view', $invoice);

        try {
            $pdf = $pdfGenerator->generateInvoicePdf($invoice);

            return new Response(
                $pdf,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="facture-' . $invoice->getNumber() . '.pdf"'
                ]
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }
    }

    #[Route('/{id}/duplicate', name: 'app_invoices_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('view', $invoice);

        if ($this->isCsrfTokenValid('duplicate'.$invoice->getId(), $request->request->get('_token'))) {
            $newInvoice = new Invoice();
            $newInvoice->setCompany($invoice->getCompany());
            $newInvoice->setCustomer($invoice->getCustomer());
            $newInvoice->setCreatedBy($this->getUser());
            $newInvoice->setSubject($invoice->getSubject() . ' (Copie)');
            $newInvoice->setDescription($invoice->getDescription());
            $newInvoice->setPaymentTerms($invoice->getPaymentTerms());
            $newInvoice->setNotes($invoice->getNotes());

            // Copy items
            foreach ($invoice->getItems() as $item) {
                $newItem = new InvoiceItem();
                $newItem->setProductName($item->getProductName());
                $newItem->setDescription($item->getDescription());
                $newItem->setUnitPrice($item->getUnitPrice());
                $newItem->setQuantity($item->getQuantity());
                $newItem->setUnit($item->getUnit());
                $newItem->setProduct($item->getProduct());
                $newInvoice->addItem($newItem);
            }

            $newInvoice->calculateTotals();
            
            $entityManager->persist($newInvoice);
            $entityManager->flush();

            $this->addFlash('success', 'La facture a été dupliquée avec succès.');
            return $this->redirectToRoute('app_invoices_edit', ['id' => $newInvoice->getId()]);
        }

        return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/send-reminder', name: 'app_invoices_send_reminder', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendReminder(Request $request, Invoice $invoice, EmailService $emailService, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('manage', $invoice);

        if (!$invoice->isOverdue()) {
            $this->addFlash('error', 'Cette facture n\'est pas en retard.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        if ($this->isCsrfTokenValid('send_reminder'.$invoice->getId(), $request->request->get('_token'))) {
            // Determine reminder type based on existing reminders
            $existingReminders = $invoice->getReminders()->count();
            $reminderType = match($existingReminders) {
                0 => InvoiceReminder::TYPE_FIRST,
                1 => InvoiceReminder::TYPE_SECOND,
                default => InvoiceReminder::TYPE_FINAL
            };

            $reminder = new InvoiceReminder();
            $reminder->setInvoice($invoice);
            $reminder->setType($reminderType);
            $reminder->setSubject('Relance facture ' . $invoice->getNumber());
            $reminder->setMessage($this->generateReminderMessage($invoice, $reminderType));

            try {
                $emailService->sendInvoiceReminder($reminder);
                $reminder->markAsSent();
                $entityManager->persist($reminder);
                $entityManager->flush();
                
                $this->addFlash('success', 'La relance a été envoyée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi de la relance: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
    }

    #[Route('/overdue/update', name: 'app_invoices_update_overdue', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateOverdueInvoices(InvoiceRepository $invoiceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        $overdueInvoices = $invoiceRepository->findBy([
            'company' => $company,
            'status' => Invoice::STATUS_SENT
        ]);

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            if ($invoice->isOverdue()) {
                $invoice->markAsOverdue();
                $count++;
            }
        }
        
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'updated' => $count,
            'message' => "$count factures marquées comme en retard."
        ]);
    }

    #[Route('/stats', name: 'app_invoices_stats', methods: ['GET'])]
    public function stats(InvoiceRepository $invoiceRepository): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        
        $stats = [
            'total' => $invoiceRepository->countByCompany($company),
            'draft' => $invoiceRepository->countByCompanyAndStatus($company, Invoice::STATUS_DRAFT),
            'sent' => $invoiceRepository->countByCompanyAndStatus($company, Invoice::STATUS_SENT),
            'paid' => $invoiceRepository->countByCompanyAndStatus($company, Invoice::STATUS_PAID),
            'overdue' => $invoiceRepository->countByCompanyAndStatus($company, Invoice::STATUS_OVERDUE),
            'cancelled' => $invoiceRepository->countByCompanyAndStatus($company, Invoice::STATUS_CANCELLED),
            'total_revenue' => $invoiceRepository->getTotalRevenueByCompany($company),
            'outstanding_amount' => $invoiceRepository->getOutstandingAmountByCompany($company),
        ];
        
        return new JsonResponse($stats);
    }

    #[Route('/export', name: 'app_invoices_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function export(Request $request, InvoiceRepository $invoiceRepository): Response
    {
        $company = $this->getUser()->getCompany();
        $format = $request->query->get('format', 'csv');
        
        $invoices = $invoiceRepository->findByCompany($company);
        
        if ($format === 'csv') {
            return $this->exportToCsv($invoices);
        }
        
        throw $this->createNotFoundException('Format d\'export non supporté');
    }

    private function generateReminderMessage(Invoice $invoice, string $type): string
    {
        $customerName = $invoice->getCustomer()->getDisplayName();
        $invoiceNumber = $invoice->getNumber();
        $amount = $invoice->getFormattedTotal();
        $dueDate = $invoice->getDueDate()->format('d/m/Y');
        
        return match($type) {
            InvoiceReminder::TYPE_FIRST => "Cher/Chère $customerName,\n\nNous vous rappelons que la facture $invoiceNumber d'un montant de $amount était due le $dueDate.\n\nMerci de bien vouloir procéder au règlement dans les plus brefs délais.\n\nCordialement,",
            InvoiceReminder::TYPE_SECOND => "Cher/Chère $customerName,\n\nMalgré notre précédent rappel, la facture $invoiceNumber d'un montant de $amount demeure impayée.\n\nNous vous remercions de régulariser cette situation rapidement.\n\nCordialement,",
            InvoiceReminder::TYPE_FINAL => "Cher/Chère $customerName,\n\nDernière relance concernant la facture $invoiceNumber d'un montant de $amount.\n\nÀ défaut de règlement sous 8 jours, nous nous verrons contraints d'engager des poursuites.\n\nCordialement,",
            default => "Rappel de paiement pour la facture $invoiceNumber."
        };
    }

    private function exportToCsv(array $invoices): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="factures-export-' . date('Y-m-d') . '.csv"');
        
        $handle = fopen('php://temp', 'r+');
        
        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Headers CSV
        fputcsv($handle, [
            'Numéro',
            'Client',
            'Sujet',
            'Date facture',
            'Date échéance',
            'Statut',
            'Sous-total HT',
            'TVA',
            'Total TTC',
            'Créé le',
            'Créé par'
        ], ';');
        
        // Données
        foreach ($invoices as $invoice) {
            fputcsv($handle, [
                $invoice->getNumber(),
                $invoice->getCustomer()->getDisplayName(),
                $invoice->getSubject(),
                $invoice->getInvoiceDate()->format('d/m/Y'),
                $invoice->getDueDate()->format('d/m/Y'),
                $invoice->getStatusLabel(),
                $invoice->getSubtotal(),
                $invoice->getVatAmount(),
                $invoice->getTotal(),
                $invoice->getCreatedAt()->format('d/m/Y H:i'),
                $invoice->getCreatedBy()->getFullName()
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        $response->setContent($content);
        
        return $response;
    }
}