<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findPageNewestFirst(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.company', 'c')
            ->andWhere('r.flagged = false')
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC') // second order by, to ensure consistent ordering when createdAt is the same
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasRecentSubmission(string $authorEmail, Company $company, \DateInterval $window): bool
    {
        $since = (new \DateTimeImmutable())->sub($window);

        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.authorEmail = :email')
            ->andWhere('r.company = :company')
            ->andWhere('r.createdAt > :since')
            ->setParameter('email', $authorEmail)
            ->setParameter('company', $company)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
