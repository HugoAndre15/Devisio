<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Form\QuoteType;
use App\Repository\QuoteRepository;
use App\Service\PdfGeneratorService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $query = $quoteRepository->createQueryBuilder('q')
            ->andWhere('q.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('q.customer', 'c')
            ->addSelect('c')
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery();

        $quotes = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('quote/index.html.twig', [
            'quotes' => $quotes,
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

            $quote->calculateTotals();
            
            $entityManager->persist($quote);
            $entityManager->flush();

            $this->addFlash('success', 'Le devis a été créé avec succès.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        return $this->render('quote/new.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quotes_show', methods: ['GET'])]
    public function show(Quote $quote): Response
    {
        $this->denyAccessUnlessGranted('view', $quote);

        return $this->render('quote/show.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quotes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $quote);

        if (!$quote->canBeModified()) {
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

            $quote->calculateTotals();
            $quote->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            $this->addFlash('success', 'Le devis a été modifié avec succès.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        return $this->render('quote/edit.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_quotes_delete', methods: ['POST'])]
    public function delete(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $quote);

        if ($this->isCsrfTokenValid('delete'.$quote->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quote);
            $entityManager->flush();
            $this->addFlash('success', 'Le devis a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quotes');
    }

    #[Route('/{id}/send', name: 'app_quotes_send', methods: ['POST'])]
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
                
                $this->addFlash('success', 'Le devis a été envoyé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi du devis: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/accept', name: 'app_quotes_accept', methods: ['POST'])]
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
            
            $this->addFlash('success', 'Le devis a été accepté avec succès.');
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_quotes_reject', methods: ['POST'])]
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
            
            $this->addFlash('success', 'Le devis a été refusé.');
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/pdf', name: 'app_quotes_pdf', methods: ['GET'])]
    public function pdf(Quote $quote, PdfGeneratorService $pdfGenerator): Response
    {
        $this->denyAccessUnlessGranted('view', $quote);

        $pdf = $pdfGenerator->generateQuotePdf($quote);

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="devis-' . $quote->getNumber() . '.pdf"'
            ]
        );
    }

    #[Route('/{id}/duplicate', name: 'app_quotes_duplicate', methods: ['POST'])]
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
            return $this->redirectToRoute('app_quotes_show', ['id' => $newQuote->getId()]);
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }

    #[Route('/{id}/convert-to-invoice', name: 'app_quotes_convert_to_invoice', methods: ['POST'])]
    public function convertToInvoice(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('convert', $quote);

        if (!$quote->canBeConvertedToInvoice()) {
            $this->addFlash('error', 'Ce devis ne peut pas être converti en facture.');
            return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
        }

        if ($this->isCsrfTokenValid('convert'.$quote->getId(), $request->request->get('_token'))) {
            $invoice = new \App\Entity\Invoice();
            $invoice->createFromQuote($quote);
            
            $entityManager->persist($invoice);
            $entityManager->flush();

            $this->addFlash('success', 'Le devis a été converti en facture avec succès.');
            return $this->redirectToRoute('app_invoices_show', ['id' => $invoice->getId()]);
        }

        return $this->redirectToRoute('app_quotes_show', ['id' => $quote->getId()]);
    }
}