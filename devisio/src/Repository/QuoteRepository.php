<?php

namespace App\Repository;

use App\Entity\Quote;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCompanyAndStatus(Company $company, string $status): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.company = :company')
            ->andWhere('q.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentByCompany(Company $company, int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('q.customer', 'c')
            ->addSelect('c')
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('q.customer', 'c')
            ->addSelect('c')
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiredQuotes(): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.status = :status')
            ->andWhere('q.validUntil < :now')
            ->setParameter('status', Quote::STATUS_SENT)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function getTotalAmountByCompany(Company $company): float
    {
        $result = $this->createQueryBuilder('q')
            ->select('SUM(q.total)')
            ->andWhere('q.company = :company')
            ->andWhere('q.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', Quote::STATUS_ACCEPTED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function getMonthlyQuotesByCompany(Company $company, int $months = 12): array
    {
        $startDate = new \DateTime('-' . ($months - 1) . ' months');
        $startDate->modify('first day of this month');

        // Utilisation de SQL natif pour DATE_FORMAT
        $sql = "
            SELECT 
                DATE_FORMAT(q.created_at, '%Y-%m') as month, 
                COUNT(q.id) as count
            FROM quote q 
            WHERE q.company_id = :companyId 
            AND q.created_at >= :startDate
            GROUP BY month
            ORDER BY month ASC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery([
            'companyId' => $company->getId(),
            'startDate' => $startDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }
}