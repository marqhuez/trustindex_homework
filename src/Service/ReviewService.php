<?php

namespace App\Service;

use App\Entity\Review;
use App\Form\Dto\CreateReviewRequest;
use Doctrine\ORM\EntityManagerInterface;

final class ReviewService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyResolver $companyResolver,
        private readonly ReviewSpamDetector $spamDetector,
    ) {
    }

    public function saveNew(Review $review): void
    {
        $now = new \DateTimeImmutable();

        $review->setCreatedAt($now);
        $review->setUpdatedAt($now);

        // find-or-create in CompanyResolver plus this flush is not atomic: two concurrent
        // submissions for the same brand-new company name can both pass the "not found" check
        // and both try to insert it. The unique constraint on Company::$name (see migrations)
        // makes the second one fail loudly instead of silently duplicating — accepted as-is for
        // this app's scale rather than adding retry/locking around a rare race.
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
