<?php

namespace App\Controller;

use App\Entity\Company;
use App\Form\AdminCompanyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/company')]
#[IsGranted('ROLE_ADMIN')]
class AdminCompanyController extends AbstractController
{
    #[Route('/', name: 'app_admin_company', methods: ['GET'])]
    public function index(): Response
    {
        $company = $this->getUser()->getCompany();
        
        // Statistiques globales de l'entreprise
        $stats = [
            'users_count' => $company->getUsers()->count(),
            'active_users' => $company->getUsers()->filter(fn($user) => $user->isActive())->count(),
            'customers_count' => $company->getCustomers()->count(),
            'active_customers' => $company->getCustomers()->filter(fn($customer) => $customer->isActive())->count(),
            'products_count' => $company->getProducts()->count(),
            'active_products' => $company->getProducts()->filter(fn($product) => $product->isActive())->count(),
            'quotes_count' => $company->getQuotes()->count(),
            'invoices_count' => $company->getInvoices()->count(),
            'total_revenue' => 0,
            'pending_revenue' => 0,
        ];

        // Calcul du CA
        foreach ($company->getInvoices() as $invoice) {
            if ($invoice->getStatus() === 'paid') {
                $stats['total_revenue'] += (float) $invoice->getTotal();
            } elseif (in_array($invoice->getStatus(), ['sent', 'overdue'])) {
                $stats['pending_revenue'] += (float) $invoice->getTotal();
            }
        }

        return $this->render('admin/company/index.html.twig', [
            'company' => $company,
            'stats' => $stats,
        ]);
    }

    #[Route('/edit', name: 'app_admin_company_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $company = $this->getUser()->getCompany();
        
        $form = $this->createForm(AdminCompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    
                    // Supprimer l'ancien logo s'il existe
                    if ($company->getLogo()) {
                        $oldLogoPath = $this->getParameter('logos_directory').'/'.$company->getLogo();
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }
                    
                    $company->setLogo($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo: ' . $e->getMessage());
                }
            }

            $company->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Les informations de l\'entreprise ont été mises à jour avec succès.');
            return $this->redirectToRoute('app_admin_company');
        }

        return $this->render('admin/company/edit.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/logo/remove', name: 'app_admin_company_remove_logo', methods: ['POST'])]
    public function removeLogo(Request $request, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()->getCompany();

        if ($this->isCsrfTokenValid('remove_logo', $request->request->get('_token'))) {
            if ($company->getLogo()) {
                // Supprimer le fichier
                $logoPath = $this->getParameter('logos_directory').'/'.$company->getLogo();
                if (file_exists($logoPath)) {
                    unlink($logoPath);
                }
                
                // Supprimer la référence en BDD
                $company->setLogo(null);
                $company->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
                
                $this->addFlash('success', 'Le logo a été supprimé avec succès.');
            }
        }

        return $this->redirectToRoute('app_admin_company_edit');
    }

    #[Route('/stats', name: 'app_admin_company_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        
        $stats = [
            'general' => [
                'created_at' => $company->getCreatedAt()->format('Y-m-d'),
                'users_count' => $company->getUsers()->count(),
                'customers_count' => $company->getCustomers()->count(),
                'products_count' => $company->getProducts()->count(),
                'quotes_count' => $company->getQuotes()->count(),
                'invoices_count' => $company->getInvoices()->count(),
            ],
            'financial' => [
                'total_revenue' => 0,
                'pending_revenue' => 0,
                'overdue_revenue' => 0,
            ],
            'activity' => [
                'quotes_this_month' => 0,
                'invoices_this_month' => 0,
                'new_customers_this_month' => 0,
            ]
        ];

        $currentMonth = new \DateTime('first day of this month');
        
        foreach ($company->getInvoices() as $invoice) {
            if ($invoice->getStatus() === 'paid') {
                $stats['financial']['total_revenue'] += (float) $invoice->getTotal();
            } elseif ($invoice->getStatus() === 'sent') {
                $stats['financial']['pending_revenue'] += (float) $invoice->getTotal();
            } elseif ($invoice->getStatus() === 'overdue') {
                $stats['financial']['overdue_revenue'] += (float) $invoice->getTotal();
            }
            
            if ($invoice->getCreatedAt() >= $currentMonth) {
                $stats['activity']['invoices_this_month']++;
            }
        }

        foreach ($company->getQuotes() as $quote) {
            if ($quote->getCreatedAt() >= $currentMonth) {
                $stats['activity']['quotes_this_month']++;
            }
        }

        foreach ($company->getCustomers() as $customer) {
            if ($customer->getCreatedAt() >= $currentMonth) {
                $stats['activity']['new_customers_this_month']++;
            }
        }
        
        return new JsonResponse($stats);
    }

    #[Route('/backup', name: 'app_admin_company_backup', methods: ['POST'])]
    public function backup(): JsonResponse
    {
        // Cette fonctionnalité nécessiterait une implémentation plus complexe
        // avec sauvegarde de la base de données, des fichiers, etc.
        
        $this->addFlash('info', 'Fonctionnalité de sauvegarde en cours de développement.');
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Fonctionnalité en cours de développement'
        ]);
    }

    #[Route('/settings/reset', name: 'app_admin_company_reset_settings', methods: ['POST'])]
    public function resetSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('reset_settings', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_company');
        }

        $company = $this->getUser()->getCompany();
        
        // Réinitialiser certains paramètres par défaut
        $company->setVatRate('20.00');
        $company->setUpdatedAt(new \DateTimeImmutable());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Les paramètres par défaut ont été restaurés.');
        return $this->redirectToRoute('app_admin_company');
    }
}