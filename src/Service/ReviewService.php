<?php

namespace App\Service;

use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function saveNew(Review $review)
    {
        $now = new \DateTimeImmutable();

        $review->setCreatedAt($now);
        $review->setUpdatedAt($now);

        $this->entityManager->persist($review);
        $this->entityManager->flush();
    }
}
