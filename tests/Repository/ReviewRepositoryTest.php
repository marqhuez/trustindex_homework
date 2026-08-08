<?php

namespace App\Tests\Repository;

use App\Entity\Company;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReviewRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReviewRepository $reviewRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->reviewRepository = self::getContainer()->get(ReviewRepository::class);

        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();
        $this->entityManager->close();

        parent::tearDown();
    }

    public function testFlaggedReviewsAreExcludedFromThePublicListing(): void
    {
        $company = $this->createCompany('Moderation Test Co');
        $visible = $this->createReview($company, flagged: false);
        $this->createReview($company, flagged: true);

        $this->entityManager->flush();

        $results = $this->reviewRepository->findPageNewestFirst(limit: 10, offset: 0);

        self::assertCount(1, $results);
        self::assertSame($visible->getId(), $results[0]->getId());
    }

    public function testHasRecentSubmissionIsTrueWithinTheWindowAndFalseOutsideIt(): void
    {
        $company = $this->createCompany('Duplicate Window Co');
        $review = $this->createReview($company, authorEmail: 'repeat@example.com');
        $this->entityManager->flush();

        self::assertTrue($this->reviewRepository->hasRecentSubmission(
            'repeat@example.com',
            $company,
            new \DateInterval('PT1H'),
        ));

        // backdate the review past a 1-hour window by setting createdAt directly
        $review->setCreatedAt(new \DateTimeImmutable('-2 hours'));
        $this->entityManager->flush();

        self::assertFalse($this->reviewRepository->hasRecentSubmission(
            'repeat@example.com',
            $company,
            new \DateInterval('PT1H'),
        ));
    }

    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);
        $this->entityManager->persist($company);

        return $company;
    }

    private function createReview(Company $company, bool $flagged = false, string $authorEmail = 'reviewer@example.com'): Review
    {
        $review = new Review();
        $review->setCompany($company);
        $review->setRating(4);
        $review->setReviewText('A perfectly ordinary review.');
        $review->setAuthorEmail($authorEmail);
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setUpdatedAt(new \DateTimeImmutable());
        $review->setFlagged($flagged);
        $this->entityManager->persist($review);

        return $review;
    }
}
