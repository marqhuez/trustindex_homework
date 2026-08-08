<?php

namespace App\Tests\Controller;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CompanyControllerTest extends WebTestCase
{
    private const array TEST_COMPANY_NAMES = ['Acme Testing Corp', 'Zeta Testing Ltd'];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        foreach (self::TEST_COMPANY_NAMES as $name) {
            $company = new Company();
            $company->setName($name);
            $entityManager->persist($company);
        }
        $entityManager->flush();
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $companyRepository = $entityManager->getRepository(Company::class);

        foreach (self::TEST_COMPANY_NAMES as $name) {
            $company = $companyRepository->findOneBy(['name' => $name]);
            if (null !== $company) {
                $entityManager->remove($company);
            }
        }
        $entityManager->flush();

        parent::tearDown();
    }

    public function testCompaniesPageListsSeededCompanies(): void
    {
        $this->client->request('GET', '/companies');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Acme Testing Corp');
        self::assertSelectorTextContains('body', 'Zeta Testing Ltd');
    }

    public function testSearchFiltersToMatchingCompanyOnly(): void
    {
        $crawler = $this->client->request('GET', '/companies', ['q' => 'Acme Testing']);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Acme Testing Corp', $crawler->text());
        self::assertStringNotContainsString('Zeta Testing Ltd', $crawler->text());
    }

    public function testXmlHttpRequestReturnsOnlyTheResultsFragmentNotTheFullPage(): void
    {
        $this->client->request(
            'GET',
            '/companies',
            ['q' => 'Acme Testing'],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        self::assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        self::assertStringContainsString('Acme Testing Corp', $content);
        self::assertStringNotContainsString('<html', $content);
    }

    public function testSearchEndpointReturnsMatchingCompaniesAsJson(): void
    {
        $this->client->request('GET', '/companies/search', ['q' => 'Zeta']);

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());

        $results = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $results);
        self::assertSame('Zeta Testing Ltd', $results[0]['name']);
    }

    public function testSearchEndpointReturnsEmptyArrayForBlankQuery(): void
    {
        $this->client->request('GET', '/companies/search', ['q' => '']);

        self::assertResponseIsSuccessful();
        self::assertSame('[]', $this->client->getResponse()->getContent());
    }
}
