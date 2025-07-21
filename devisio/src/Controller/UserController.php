<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();
        
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe a été saisi
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/stats', name: 'app_user_stats', methods: ['GET'])]
    public function stats(): Response
    {
        $user = $this->getUser();
        
        // Statistiques personnelles de l'utilisateur
        $stats = [
            'quotes_created' => $user->getQuotes()->count(),
            'invoices_created' => $user->getInvoices()->count(),
            'total_quotes_amount' => 0,
            'total_invoices_amount' => 0,
            'quotes_by_status' => [
                'draft' => 0,
                'sent' => 0,
                'accepted' => 0,
                'rejected' => 0,
                'expired' => 0,
            ],
            'invoices_by_status' => [
                'draft' => 0,
                'sent' => 0,
                'paid' => 0,
                'overdue' => 0,
                'cancelled' => 0,
            ],
        ];

        // Calculer les statistiques des devis
        foreach ($user->getQuotes() as $quote) {
            $stats['total_quotes_amount'] += (float) $quote->getTotal();
            $stats['quotes_by_status'][$quote->getStatus()]++;
        }

        // Calculer les statistiques des factures
        foreach ($user->getInvoices() as $invoice) {
            $stats['total_invoices_amount'] += (float) $invoice->getTotal();
            $stats['invoices_by_status'][$invoice->getStatus()]++;
        }

        // Activité récente (derniers devis et factures)
        $recentQuotes = $user->getQuotes()
            ->matching(
                \Doctrine\Common\Collections\Criteria::create()
                    ->orderBy(['createdAt' => 'DESC'])
                    ->setMaxResults(5)
            );

        $recentInvoices = $user->getInvoices()
            ->matching(
                \Doctrine\Common\Collections\Criteria::create()
                    ->orderBy(['createdAt' => 'DESC'])
                    ->setMaxResults(5)
            );

        return $this->render('user/stats.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'recent_quotes' => $recentQuotes,
            'recent_invoices' => $recentInvoices,
        ]);
    }
}