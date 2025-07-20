<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Form\QuoteType;
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

#[Route('/quotes')]
#[IsGranted('ROLE_USER')]
class QuoteController extends AbstractController
{
    #[Route('/', name: 'app_quotes', methods: ['GET'])]
    public function index(Request $request, QuoteRepository $quoteRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $quoteRepository->createQueryBuilder('q')
            ->andWhere('q.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('q.customer', 'c')
            ->addSelect('c')
            ->orderBy('q.createdAt', 'DESC');

        // Filtrage par statut si demandé
        $status = $request->query->get('status');
        if ($status && in_array($status, ['draft', 'sent', 'accepted', 'rejected', 'expired'])) {
            $queryBuilder->andWhere('q.status = :status')
                        ->setParameter('status', $status);
        }

        // Recherche par texte
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('q.number LIKE :search OR q.subject LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search OR c.companyName LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $quotes = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques pour la vue
        $stats = [
            'total' => $quoteRepository->countByCompany($company),
            'draft' => $quoteRepository->countByCompanyAndStatus($company, 'draft'),
            'sent' => $quoteRepository->countByCompanyAndStatus($company, 'sent'),
            'accepted' => $quoteRepository->countByCompanyAndStatus($company, 'accepted'),
            'rejected' => $quoteRepository->countByCompanyAndStatus($company, 'rejected'),
        ];

        return $this->render('quote/index.html.twig', [
            'quotes' => $quotes,
            'stats' => $stats,
            'current_status' => $status,
            'current_search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_quotes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quote = new Quote();
        $quote->setCompany($this->getUser()->getCompany());
        $quote->setCreatedBy($this->getUser());
        
        // Add an empty item to start
        $quote->addItem(new QuoteItem());

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Remove empty items
            foreach ($quote->getItems() as $item) {
                if (!$item->getProductName()) {
                    $quote->removeItem($item);
                }
            }

            if ($quote->getItems()->isEmpty()) {
                $this->addFlash('error', 'Le devis doit contenir au moins un article.');
                return $this->render('quote/new.html.twig', [
                    'quote' => $quote,
                    'form' => $form,
                ]);
            }

            $quote->calculateTotals();
            
            $entityManager->persist($quote);
            $entityManager->flush();

            // Check if user wants to send immediately
            $action = $request->request->get('action');
            if ($action === 'save_and_send' && $quote->canBeSent()) {
                try {
                    $emailService = $this->container->get(EmailService::class);
                    $emailService->sendQuoteToCustomer($quote);
                    $quote->markAsSent();
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Le devis a été créé et envoyé avec succès.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Le devis a été créé mais l\'envoi a échoué: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('success', 'Le devis a été créé avec succès.');
            }

            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        return $this->render('quote/new.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quotes_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Quote $quote): Response
    {
        $this->denyAccessUnlessGranted('view', $quote);

        return $this->render('quote/show.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quotes_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $quote);

        if (!$quote->canBeModified() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Ce devis ne peut plus être modifié.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Remove empty items
            foreach ($quote->getItems() as $item) {
                if (!$item->getProductName()) {
                    $quote->removeItem($item);
                }
            }

            if ($quote->getItems()->isEmpty()) {
                $this->addFlash('error', 'Le devis doit contenir au moins un article.');
                return $this->render('quote/edit.html.twig', [
                    'quote' => $quote,
                    'form' => $form,
                ]);
            }

            $quote->calculateTotals();
            $quote->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            // Check if user wants to send immediately
            $action = $request->request->get('action');
            if ($action === 'save_and_send' && $quote->canBeSent()) {
                try {
                    $emailService = $this->container->get(EmailService::class);
                    $emailService->sendQuoteToCustomer($quote);
                    $quote->markAsSent();
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Le devis a été modifié et envoyé avec succès.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Le devis a été modifié mais l\'envoi a échoué: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('success', 'Le devis a été modifié avec succès.');
            }

            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        return $this->render('quote/edit.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_quotes_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $quote);

        if ($this->isCsrfTokenValid('delete'.$quote->getId(), $request->request->get('_token'))) {
            // Vérifier qu'on peut supprimer
            if ($quote->getStatus() !== Quote::STATUS_DRAFT) {
                $this->addFlash('error', 'Seuls les devis en brouillon peuvent être supprimés.');
                return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
            }

            $entityManager->remove($quote);
            $entityManager->flush();
            $this->addFlash('success', 'Le devis a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quotes');
    }

    #[Route('/{id}/send', name: 'app_quotes_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(Request $request, Quote $quote, EmailService $emailService, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('send', $quote);

        if (!$quote->canBeSent()) {
            $this->addFlash('error', 'Ce devis ne peut pas être envoyé.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        if ($this->isCsrfTokenValid('send'.$quote->getId(), $request->request->get('_token'))) {
            try {
                $emailService->sendQuoteToCustomer($quote);
                $quote->markAsSent();
                $entityManager->flush();
                
                $this->addFlash('success', 'Le devis a été envoyé avec succès à ' . $quote->getCustomer()->getEmail());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi du devis: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/accept', name: 'app_quotes_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('accept', $quote);

        if (!$quote->canBeAccepted()) {
            $this->addFlash('error', 'Ce devis ne peut pas être accepté.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        if ($this->isCsrfTokenValid('accept'.$quote->getId(), $request->request->get('_token'))) {
            $quote->markAsAccepted();
            $entityManager->flush();
            
            $this->addFlash('success', 'Le devis a été marqué comme accepté.');
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_quotes_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('reject', $quote);

        if (!$quote->canBeRejected()) {
            $this->addFlash('error', 'Ce devis ne peut pas être refusé.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        if ($this->isCsrfTokenValid('reject'.$quote->getId(), $request->request->get('_token'))) {
            $quote->markAsRejected();
            $entityManager->flush();
            
            $this->addFlash('success', 'Le devis a été marqué comme refusé.');
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/pdf', name: 'app_quotes_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(Quote $quote, PdfGeneratorService $pdfGenerator): Response
    {
        $this->denyAccessUnlessGranted('view', $quote);

        try {
            $pdf = $pdfGenerator->generateQuotePdf($quote);

            return new Response(
                $pdf,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="devis-' . $quote->getNumber() . '.pdf"'
                ]
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }
    }

    #[Route('/{id}/duplicate', name: 'app_quotes_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('view', $quote);

        if ($this->isCsrfTokenValid('duplicate'.$quote->getId(), $request->request->get('_token'))) {
            $newQuote = new Quote();
            $newQuote->setCompany($quote->getCompany());
            $newQuote->setCustomer($quote->getCustomer());
            $newQuote->setCreatedBy($this->getUser());
            $newQuote->setSubject($quote->getSubject() . ' (Copie)');
            $newQuote->setDescription($quote->getDescription());
            $newQuote->setTerms($quote->getTerms());
            $newQuote->setNotes($quote->getNotes());

            // Copy items
            foreach ($quote->getItems() as $item) {
                $newItem = new QuoteItem();
                $newItem->setProductName($item->getProductName());
                $newItem->setDescription($item->getDescription());
                $newItem->setUnitPrice($item->getUnitPrice());
                $newItem->setQuantity($item->getQuantity());
                $newItem->setUnit($item->getUnit());
                $newItem->setProduct($item->getProduct());
                $newQuote->addItem($newItem);
            }

            $newQuote->calculateTotals();
            
            $entityManager->persist($newQuote);
            $entityManager->flush();

            $this->addFlash('success', 'Le devis a été dupliqué avec succès.');
            return $this->redirectToRoute('app_quotes_edit', ['id' => $newQuote->getId()]);
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/convert-to-invoice', name: 'app_quotes_convert_to_invoice', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function convertToInvoice(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('convert', $quote);

        if (!$quote->canBeConvertedToInvoice()) {
            $this->addFlash('error', 'Ce devis ne peut pas être converti en facture.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        if ($this->isCsrfTokenValid('convert'.$quote->getId(), $request->request->get('_token'))) {
            // Créer directement la facture sans formulaire de modification
            $invoice = new Invoice();
            $invoice->createFromQuote($quote);
            $invoice->setCreatedBy($this->getUser());
            
            $entityManager->persist($invoice);
            $entityManager->flush();

            $this->addFlash('success', 'La facture ' . $invoice->getNumber() . ' a été créée avec succès depuis le devis.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/api/products/{id}', name: 'api_product_details', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProductDetails(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $entityManager->getRepository(\App\Entity\Product::class)->find($id);
        
        if (!$product || $product->getCompany() !== $this->getUser()->getCompany()) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        return new JsonResponse([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'unit' => $product->getUnit(),
            'type' => $product->getType(),
        ]);
    }

    #[Route('/expired/update', name: 'app_quotes_update_expired', methods: ['POST'])]
    public function updateExpiredQuotes(QuoteRepository $quoteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $expiredQuotes = $quoteRepository->findExpiredQuotes();
        $count = 0;
        
        foreach ($expiredQuotes as $quote) {
            if ($quote->getCompany() === $this->getUser()->getCompany()) {
                $quote->markAsExpired();
                $count++;
            }
        }
        
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'updated' => $count,
            'message' => "$count devis expirés mis à jour."
        ]);
    }

    #[Route('/stats', name: 'app_quotes_stats', methods: ['GET'])]
    public function stats(QuoteRepository $quoteRepository): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        
        $stats = [
            'total' => $quoteRepository->countByCompany($company),
            'draft' => $quoteRepository->countByCompanyAndStatus($company, Quote::STATUS_DRAFT),
            'sent' => $quoteRepository->countByCompanyAndStatus($company, Quote::STATUS_SENT),
            'accepted' => $quoteRepository->countByCompanyAndStatus($company, Quote::STATUS_ACCEPTED),
            'rejected' => $quoteRepository->countByCompanyAndStatus($company, Quote::STATUS_REJECTED),
            'expired' => $quoteRepository->countByCompanyAndStatus($company, Quote::STATUS_EXPIRED),
            'total_amount' => $quoteRepository->getTotalAmountByCompany($company),
        ];
        
        return new JsonResponse($stats);
    }

    #[Route('/export', name: 'app_quotes_export', methods: ['GET'])]
    public function export(Request $request, QuoteRepository $quoteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $company = $this->getUser()->getCompany();
        $format = $request->query->get('format', 'csv');
        
        $quotes = $quoteRepository->findByCompany($company);
        
        if ($format === 'csv') {
            return $this->exportToCsv($quotes);
        }
        
        throw $this->createNotFoundException('Format d\'export non supporté');
    }

    private function exportToCsv(array $quotes): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="devis-export-' . date('Y-m-d') . '.csv"');
        
        $handle = fopen('php://temp', 'r+');
        
        // Headers CSV
        fputcsv($handle, [
            'Numéro',
            'Client',
            'Sujet',
            'Date',
            'Valide jusqu\'au',
            'Statut',
            'Sous-total HT',
            'TVA',
            'Total TTC',
            'Créé le',
            'Créé par'
        ], ';');
        
        // Données
        foreach ($quotes as $quote) {
            fputcsv($handle, [
                $quote->getNumber(),
                $quote->getCustomer()->getDisplayName(),
                $quote->getSubject(),
                $quote->getQuoteDate()->format('d/m/Y'),
                $quote->getValidUntil()->format('d/m/Y'),
                $quote->getStatusLabel(),
                $quote->getSubtotal(),
                $quote->getVatAmount(),
                $quote->getTotal(),
                $quote->getCreatedAt()->format('d/m/Y H:i'),
                $quote->getCreatedBy()->getFullName()
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        $response->setContent($content);
        
        return $response;
    }
}