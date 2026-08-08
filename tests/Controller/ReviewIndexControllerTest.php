<?php

namespace App\Tests\Controller;

use App\Entity\Company;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewIndexControllerTest extends WebTestCase
{
    private const string TEST_COMPANY_NAME = 'Pagination Test Co';
    private const int SEEDED_REVIEW_COUNT = 15;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $company = new Company();
        $company->setName(self::TEST_COMPANY_NAME);
        $this->entityManager->persist($company);

        for ($i = 0; $i < self::SEEDED_REVIEW_COUNT; ++$i) {
            $review = new Review();
            $review->setCompany($company);
            $review->setRating(5);
            $review->setReviewText('Seeded review number '.$i);
            $review->setAuthorEmail(sprintf('pagination-test-%d@example.com', $i));
            $review->setCreatedAt(new \DateTimeImmutable());
            $review->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($review);
        }

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $reviews = $entityManager->getRepository(Review::class)->findBy(['authorEmail' => array_map(
            static fn (int $i) => sprintf('pagination-test-%d@example.com', $i),
            range(0, self::SEEDED_REVIEW_COUNT - 1),
        )]);
        foreach ($reviews as $review) {
            $entityManager->remove($review);
        }
        $entityManager->flush();

        $company = $entityManager->getRepository(Company::class)->findOneBy(['name' => self::TEST_COMPANY_NAME]);
        if (null !== $company) {
            $entityManager->remove($company);
            $entityManager->flush();
        }

        parent::tearDown();
    }

    public function testPageZeroClampsToPageOne(): void
    {
        $this->client->request('GET', '/?page=0');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[aria-current="page"]', '1');
    }

    public function testPageBeyondTheLastPageClampsToTheLastPage(): void
    {
        $this->client->request('GET', '/?page=999');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[aria-current="page"]', '2');
    }

    public function testNegativePageClampsToPageOne(): void
    {
        $this->client->request('GET', '/?page=-5');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[aria-current="page"]', '1');
    }
}
