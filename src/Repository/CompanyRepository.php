<?php

namespace App\Repository;

use App\Entity\Company;
use App\Model\CompanyStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * @return CompanyStats[]
     */
    public function findAllWithReviewStats(): array
    {
        return $this->createQueryBuilder('c')
            ->select(sprintf('NEW %s(c, COUNT(r.id), AVG(r.rating))', CompanyStats::class))
            ->leftJoin('c.reviews', 'r')
            ->groupBy('c.id')
            ->orderBy('AVG(r.rating)', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNameIgnoreCase(string $name): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Company[]
     */
    public function search(string $query, int $limit = 8): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
