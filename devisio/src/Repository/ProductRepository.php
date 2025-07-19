<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->setParameter('company', $company)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByCompany(Company $company): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeAndCompany(Company $company, string $type): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.type = :type')
            ->andWhere('p.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByCompany(Company $company, string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithPrices(Company $company): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('p.prices', 'pr')
            ->leftJoin('pr.season', 's')
            ->addSelect('pr', 's')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMostUsedProducts(Company $company, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p, COUNT(qi.id) as usage_count')
            ->andWhere('p.company = :company')
            ->leftJoin('p.quoteItems', 'qi')
            ->setParameter('company', $company)
            ->groupBy('p.id')
            ->orderBy('usage_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getProductsByCategory(Company $company): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.type, COUNT(p.id) as count')
            ->andWhere('p.company = :company')
            ->andWhere('p.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->groupBy('p.type')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }
}