<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $company = $this->getUser()->getCompany();
        
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->andWhere('u.company = :company')
            ->setParameter('company', $company)
            ->orderBy('u.createdAt', 'DESC');

        // Filtrage par statut
        $status = $request->query->get('status');
        if ($status !== null) {
            $isActive = $status === 'active';
            $queryBuilder->andWhere('u.isActive = :isActive')
                        ->setParameter('isActive', $isActive);
        }

        // Filtrage par rôle
        $role = $request->query->get('role');
        if ($role && in_array($role, ['ROLE_ADMIN', 'ROLE_COMPTABLE', 'ROLE_EMPLOYE'])) {
            $queryBuilder->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%' . $role . '%');
        }

        // Recherche par texte
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $users = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Statistiques
        $stats = [
            'total' => $userRepository->countByCompany($company),
            'active' => $userRepository->countActiveByCompany($company),
            'admins' => $userRepository->countByCompanyAndRole($company, 'ROLE_ADMIN'),
            'comptables' => $userRepository->countByCompanyAndRole($company, 'ROLE_COMPTABLE'),
            'employes' => $userRepository->countByCompanyAndRole($company, 'ROLE_EMPLOYE'),
        ];

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'current_status' => $status,
            'current_role' => $role,
            'current_search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $user->setCompany($this->getUser()->getCompany());
        
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur ' . $user->getFullName() . ' a été créé avec succès.');
            return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('view', $user);

        // Statistiques de l'utilisateur
        $stats = [
            'quotes_count' => $user->getQuotes()->count(),
            'invoices_count' => $user->getInvoices()->count(),
            'total_quotes_amount' => 0,
            'total_invoices_amount' => 0,
            'ca_generated' => 0,
        ];

        foreach ($user->getQuotes() as $quote) {
            $stats['total_quotes_amount'] += (float) $quote->getTotal();
        }

        foreach ($user->getInvoices() as $invoice) {
            $stats['total_invoices_amount'] += (float) $invoice->getTotal();
            if ($invoice->getStatus() === 'paid') {
                $stats['ca_generated'] += (float) $invoice->getTotal();
            }
        }

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('edit', $user);

        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du nouveau mot de passe si fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur ' . $user->getFullName() . ' a été modifié avec succès.');
            return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_users_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $user);

        // Empêcher la désactivation du dernier admin
        if ($user->hasRole('ROLE_ADMIN') && $user->isActive()) {
            $activeAdmins = $entityManager->getRepository(User::class)->countActiveAdminsByCompany($user->getCompany());
            if ($activeAdmins <= 1) {
                $this->addFlash('error', 'Impossible de désactiver le dernier administrateur de l\'entreprise.');
                return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
            }
        }

        if ($this->isCsrfTokenValid('toggle_status'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $status = $user->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "L'utilisateur {$user->getFullName()} a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $user);

        // Empêcher la suppression du dernier admin
        if ($user->hasRole('ROLE_ADMIN')) {
            $activeAdmins = $entityManager->getRepository(User::class)->countActiveAdminsByCompany($user->getCompany());
            if ($activeAdmins <= 1) {
                $this->addFlash('error', 'Impossible de supprimer le dernier administrateur de l\'entreprise.');
                return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
            }
        }

        // Empêcher la suppression si l'utilisateur a des devis/factures
        if ($user->getQuotes()->count() > 0 || $user->getInvoices()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il a créé des devis ou factures.');
            return $this->redirectToRoute('app_admin_users_show', ['id' => $user->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $fullName = $user->getFullName();
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', "L'utilisateur {$fullName} a été supprimé avec succès.");
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/stats', name: 'app_admin_users_stats', methods: ['GET'])]
    public function stats(UserRepository $userRepository): JsonResponse
    {
        $company = $this->getUser()->getCompany();
        
        $stats = [
            'total' => $userRepository->countByCompany($company),
            'active' => $userRepository->countActiveByCompany($company),
            'inactive' => $userRepository->countInactiveByCompany($company),
            'by_role' => [
                'admins' => $userRepository->countByCompanyAndRole($company, 'ROLE_ADMIN'),
                'comptables' => $userRepository->countByCompanyAndRole($company, 'ROLE_COMPTABLE'),
                'employes' => $userRepository->countByCompanyAndRole($company, 'ROLE_EMPLOYE'),
            ],
            'recent_users' => $userRepository->findRecentByCompany($company, 5),
        ];
        
        return new JsonResponse($stats);
    }

    #[Route('/export', name: 'app_admin_users_export', methods: ['GET'])]
    public function export(UserRepository $userRepository): Response
    {
        $company = $this->getUser()->getCompany();
        $users = $userRepository->findByCompany($company);
        
        return $this->exportToCsv($users);
    }

    private function exportToCsv(array $users): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs-export-' . date('Y-m-d') . '.csv"');
        
        $handle = fopen('php://temp', 'r+');
        
        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Headers CSV
        fputcsv($handle, [
            'Prénom',
            'Nom',
            'Email',
            'Rôles',
            'Statut',
            'Devis créés',
            'Factures créées',
            'Créé le',
            'Dernière connexion'
        ], ';');
        
        // Données
        foreach ($users as $user) {
            fputcsv($handle, [
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail(),
                implode(', ', $user->getRoles()),
                $user->isActive() ? 'Actif' : 'Inactif',
                $user->getQuotes()->count(),
                $user->getInvoices()->count(),
                $user->getCreatedAt()->format('d/m/Y H:i'),
                $user->getUpdatedAt()->format('d/m/Y H:i')
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        $response->setContent($content);
        
        return $response;
    }
}