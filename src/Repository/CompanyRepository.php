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
        $rows = $this->createQueryBuilder('c')
            ->select('c', 'COUNT(r.id) AS reviewCount', 'AVG(r.rating) AS averageRating')
            ->leftJoin('c.reviews', 'r')
            ->groupBy('c.id')
            ->orderBy('averageRating', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(
            fn(array $row) => new CompanyStats(
                company: $row[0],
                reviewCount: (int) $row['reviewCount'],
                averageRating: $row['averageRating'] !== null ? (float) $row['averageRating'] : null,
            ),
            $rows,
        );
    }
}
