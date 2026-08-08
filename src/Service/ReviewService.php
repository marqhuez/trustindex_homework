<?php

namespace App\Service;

use App\Entity\Review;
use App\Form\Dto\CreateReviewRequest;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyResolver $companyResolver,
        private readonly ReviewSpamDetector $spamDetector,
    ) {}

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
        $company = $this->companyResolver->findOrCreateByName($request->companyName);

        $review = new Review();
        $review->setCompany($company);
        $review->setRating($request->rating);
        $review->setReviewText($request->reviewText);
        $review->setAuthorEmail($request->authorEmail);
        $review->setFlagged($this->spamDetector->isSpam($request->reviewText, $request->authorEmail, $company));

        return $review;
    }
}
