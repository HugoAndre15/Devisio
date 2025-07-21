<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Company;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countByCompany(Company $company): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveByCompany(Company $company): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.company = :company')
            ->andWhere('u.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countInactiveByCompany(Company $company): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.company = :company')
            ->andWhere('u.isActive = :active')
            ->setParameter('company', $company)
            ->setParameter('active', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCompanyAndRole(Company $company, string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.company = :company')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('company', $company)
            ->setParameter('role', '%' . $role . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveAdminsByCompany(Company $company): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.company = :company')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('company', $company)
            ->setParameter('active', true)
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.company = :company')
            ->setParameter('company', $company)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByCompany(Company $company, int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.company = :company')
            ->setParameter('company', $company)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
