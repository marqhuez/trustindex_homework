<?php

namespace App\Repository;

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

    public function findPageNewestFirst(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('c')
            ->join('r.company', 'c')
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC') // second order by, to ensure consistent ordering when createdAt is the same
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
