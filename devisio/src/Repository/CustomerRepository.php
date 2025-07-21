<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByCompany(Company $company): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->andWhere('c.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByCompany(Company $company, string $query): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->andWhere('c.isActive = :active')
            ->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query OR c.email LIKE :query OR c.companyName LIKE :query')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithQuotesAndInvoices(Company $company): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->leftJoin('c.quotes', 'q')
            ->leftJoin('c.invoices', 'i')
            ->addSelect('q', 'i')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTopCustomersByRevenue(Company $company, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->select('c, SUM(i.total) as revenue')
            ->andWhere('c.company = :company')
            ->leftJoin('c.invoices', 'i')
            ->andWhere('i.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', 'paid')
            ->groupBy('c.id')
            ->orderBy('revenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchActiveByCompany(Company $company, string $query): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->andWhere('c.isActive = :active')
            ->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query OR c.email LIKE :query OR c.companyName LIKE :query')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function searchAllByCompany(Company $company, string $query): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query OR c.email LIKE :query OR c.companyName LIKE :query')
            ->setParameter('company', $company)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.isActive', 'DESC') // Actifs en premier
            ->addOrderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}