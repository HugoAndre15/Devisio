<?php

namespace App\Repository;

use App\Entity\DiscountCode;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DiscountCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountCode::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveByCompany(Company $company): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.company = :company')
            ->andWhere('d.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countExpiredByCompany(Company $company): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.company = :company')
            ->andWhere('d.validUntil < :now')
            ->setParameter('company', $company)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUsedThisMonth(Company $company): int
    {
        $startOfMonth = new \DateTime('first day of this month');
        
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.company = :company')
            ->andWhere('d.usageCount > 0')
            ->andWhere('d.updatedAt >= :startOfMonth')
            ->setParameter('company', $company)
            ->setParameter('startOfMonth', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findValidCodeByCompany(Company $company, string $code): ?DiscountCode
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.company = :company')
            ->andWhere('d.code = :code')
            ->andWhere('d.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('code', strtoupper(trim($code)))
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByCompany(Company $company): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.company = :company')
            ->andWhere('d.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiringSoon(Company $company, int $days = 7): array
    {
        $futureDate = new \DateTime("+{$days} days");
        
        return $this->createQueryBuilder('d')
            ->andWhere('d.company = :company')
            ->andWhere('d.isActive = :active')
            ->andWhere('d.validUntil BETWEEN :now AND :futureDate')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->setParameter('futureDate', $futureDate)
            ->orderBy('d.validUntil', 'ASC')
            ->getQuery()
            ->getResult();
    }
}