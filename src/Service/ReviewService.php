<?php

namespace App\Service;

use App\Entity\Review;
use App\Form\Dto\CreateReviewRequest;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly CompanyResolver $companyResolver) {}

    public function saveNew(Review $review)
    {
        $now = new \DateTimeImmutable();

        $review->setCreatedAt($now);
        $review->setUpdatedAt($now);

        $this->entityManager->persist($review);
        $this->entityManager->flush();
    }

    public function createFromRequest(CreateReviewRequest $request): Review
    {
        $review = new Review();
        $review->setCompany($this->companyResolver->findOrCreateByName($request->companyName));
        $review->setRating($request->rating);
        $review->setReviewText($request->reviewText);
        $review->setAuthorEmail($request->authorEmail);

        return $review;
    }
}
