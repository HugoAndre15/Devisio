<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCompanyAndStatus(Company $company, string $status): int
    {
        return $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.company = :company')
            ->andWhere('i.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentByCompany(Company $company, int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueByCompany(Company $company): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.company = :company')
            ->andWhere('i.status = :status')
            ->andWhere('i.dueDate < :now')
            ->setParameter('company', $company)
            ->setParameter('status', Invoice::STATUS_SENT)
            ->setParameter('now', new \DateTime())
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInvoicesForReminder(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status IN (:statuses)')
            ->andWhere('i.dueDate < :now')
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->setParameter('now', new \DateTime())
            ->leftJoin('i.reminders', 'r')
            ->addSelect('r')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyRevenueByCompany(Company $company, int $months = 12): array
    {
        $startDate = new \DateTime('-' . ($months - 1) . ' months');
        $startDate->modify('first day of this month');

        // Utilisation de SQL natif pour DATE_FORMAT
        $sql = "
            SELECT 
                DATE_FORMAT(i.paid_at, '%Y-%m') as month, 
                SUM(i.total) as amount
            FROM invoice i 
            WHERE i.company_id = :companyId 
            AND i.status = :status
            AND i.paid_at >= :startDate
            GROUP BY month
            ORDER BY month ASC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery([
            'companyId' => $company->getId(),
            'status' => Invoice::STATUS_PAID,
            'startDate' => $startDate->format('Y-m-d H:i:s')
        ]);

        $rawResult = $result->fetchAllAssociative();

        // Fill missing months with 0
        $filledResult = [];
        for ($i = 0; $i < $months; $i++) {
            $date = new \DateTime('-' . ($months - 1 - $i) . ' months');
            $monthKey = $date->format('Y-m');
            
            $found = false;
            foreach ($rawResult as $row) {
                if ($row['month'] === $monthKey) {
                    $filledResult[] = [
                        'month' => $date,
                        'amount' => (float) $row['amount']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $filledResult[] = [
                    'month' => $date,
                    'amount' => 0.0
                ];
            }
        }

        return $filledResult;
    }

    public function getTotalRevenueByCompany(Company $company): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.total)')
            ->andWhere('i.company = :company')
            ->andWhere('i.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function getOutstandingAmountByCompany(Company $company): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.total)')
            ->andWhere('i.company = :company')
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('company', $company)
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
}