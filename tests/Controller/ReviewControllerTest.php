<?php

namespace App\Tests\Controller;

use App\Repository\CompanyRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewControllerTest extends WebTestCase
{
    private const array TEST_EMAILS = ['functional-test@example.com', 'not-an-email', 'spam-test@example.com'];
    private const string TEST_COMPANY_NAME = 'Functional Test Co';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $reviewRepository = static::getContainer()->get(ReviewRepository::class);

        foreach (self::TEST_EMAILS as $email) {
            $review = $reviewRepository->findOneBy(['authorEmail' => $email]);
            if (null !== $review) {
                $entityManager->remove($review);
            }
        }
        $entityManager->flush();

        $companyRepository = static::getContainer()->get(CompanyRepository::class);
        $company = $companyRepository->findOneByNameIgnoreCase(self::TEST_COMPANY_NAME);
        if (null !== $company) {
            $entityManager->remove($company);
            $entityManager->flush();
        }

        parent::tearDown();
    }

    public function testSubmittingAValidReviewRedirectsAndPersists(): void
    {
        $client = $this->client;

        $crawler = $client->request('GET', '/reviews/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Send review')->form([
            'review[rating]' => '5',
            'review[reviewText]' => 'Excellent service, would recommend to everyone I know.',
            'review[authorEmail]' => 'functional-test@example.com',
            'review[companyName]' => self::TEST_COMPANY_NAME,
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Review submitted successfully!');

        $reviewRepository = static::getContainer()->get(ReviewRepository::class);
        $persisted = $reviewRepository->findOneBy(['authorEmail' => 'functional-test@example.com']);

        self::assertNotNull($persisted, 'the review should have been persisted');
        self::assertSame(self::TEST_COMPANY_NAME, $persisted->getCompany()->getName());
        self::assertFalse($persisted->isFlagged());
    }

    public function testSubmittingInvalidDataRedisplaysFormWithErrorsAndPersistsNothing(): void
    {
        $client = $this->client;

        $crawler = $client->request('GET', '/reviews/new');

        $form = $crawler->selectButton('Send review')->form([
            'review[rating]' => '5',
            'review[reviewText]' => 'Some review text.',
            'review[authorEmail]' => 'not-an-email',
            'review[companyName]' => self::TEST_COMPANY_NAME,
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'This value is not a valid email address.');

        $reviewRepository = static::getContainer()->get(ReviewRepository::class);
        self::assertNull($reviewRepository->findOneBy(['authorEmail' => 'not-an-email']));
    }

    public function testShowingANonexistentReviewReturns404(): void
    {
        $this->client->request('GET', '/reviews/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testASpamTriggeringReviewIsFlaggedAndHiddenEverywhereEndToEnd(): void
    {
        $client = $this->client;

        $crawler = $client->request('GET', '/reviews/new');

        $form = $crawler->selectButton('Send review')->form([
            'review[rating]' => '5',
            'review[reviewText]' => 'Best CASINO bonuses ever, come play now!',
            'review[authorEmail]' => 'spam-test@example.com',
            'review[companyName]' => self::TEST_COMPANY_NAME,
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Thanks! Your review is pending moderation.');
        self::assertSelectorTextNotContains('body', self::TEST_COMPANY_NAME);

        $reviewRepository = static::getContainer()->get(ReviewRepository::class);
        $persisted = $reviewRepository->findOneBy(['authorEmail' => 'spam-test@example.com']);

        self::assertNotNull($persisted, 'a flagged review should still be persisted, not silently dropped');
        self::assertTrue($persisted->isFlagged());

        $client->request('GET', '/reviews/'.$persisted->getId());
        self::assertResponseStatusCodeSame(404, 'a flagged review should not be viewable by id either');
    }
}
