<?php

// SeasonRepository.php - Nouvelles méthodes

namespace App\Repository;

use App\Entity\Season;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveByCompany(Company $company): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.company = :company')
            ->andWhere('s.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findCurrentSeason(Company $company): ?Season
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.startDate <= :now')
            ->andWhere('s.endDate >= :now')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSeasonForDate(Company $company, \DateTimeInterface $date): ?Season
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.startDate <= :date')
            ->andWhere('s.endDate >= :date')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUpcomingSeasons(Company $company, int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.startDate > :now')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}