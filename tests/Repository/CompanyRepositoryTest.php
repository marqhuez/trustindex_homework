<?php

namespace App\Tests\Repository;

use App\Entity\Company;
use App\Entity\Review;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CompanyRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CompanyRepository $companyRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->companyRepository = self::getContainer()->get(CompanyRepository::class);

        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();
        $this->entityManager->close();

        parent::tearDown();
    }

    public function testCompaniesAreOrderedByAverageRatingDescending(): void
    {
        $topRated = $this->createCompanyWithReviews('Top Rated Co', [5, 5]);
        $midRated = $this->createCompanyWithReviews('Mid Rated Co', [1, 3, 4]);
        $unrated = $this->createCompany('Unrated Co');
        $expectedCompanyCount = 3;

        $this->entityManager->flush();

        $stats = $this->companyRepository->findAllWithReviewStats();

        self::assertCount($expectedCompanyCount, $stats);
        self::assertSame($topRated->getId(), $stats[0]->company->getId(), 'highest average should be first');
        self::assertSame($midRated->getId(), $stats[1]->company->getId(), 'lower average should follow');
        self::assertSame($unrated->getId(), $stats[2]->company->getId(), 'company with no reviews should sort last, not first');
    }

    public function testReviewCountAndAverageRatingAreComputedCorrectly(): void
    {
        $ratings = [1, 3, 4];
        $expectedReviewCount = count($ratings);
        $expectedAverageRating = array_sum($ratings) / $expectedReviewCount;

        $this->createCompanyWithReviews('Precision Co', $ratings);
        $this->createCompany('No Reviews Co');
        $expectedUnratedCompanyReviewCount = 0;

        $this->entityManager->flush();

        $statsByName = [];
        foreach ($this->companyRepository->findAllWithReviewStats() as $stat) {
            $statsByName[$stat->company->getName()] = $stat;
        }

        self::assertSame($expectedReviewCount, $statsByName['Precision Co']->reviewCount);
        self::assertEqualsWithDelta($expectedAverageRating, $statsByName['Precision Co']->averageRating, 0.001);

        self::assertSame($expectedUnratedCompanyReviewCount, $statsByName['No Reviews Co']->reviewCount);
        self::assertNull($statsByName['No Reviews Co']->averageRating);
    }

    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);
        $this->entityManager->persist($company);

        return $company;
    }

    /**
     * @param int[] $ratings
     */
    private function createCompanyWithReviews(string $name, array $ratings): Company
    {
        $company = $this->createCompany($name);

        foreach ($ratings as $rating) {
            $review = new Review();
            $review->setCompany($company);
            $review->setRating($rating);
            $review->setReviewText('Solid experience overall.');
            $review->setAuthorEmail('reviewer@example.com');
            $review->setCreatedAt(new \DateTimeImmutable());
            $review->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($review);
        }

        return $company;
    }
}
