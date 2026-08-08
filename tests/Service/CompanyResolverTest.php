<?php

namespace App\Tests\Service;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Service\CompanyResolver;
use PHPUnit\Framework\TestCase;

final class CompanyResolverTest extends TestCase
{
    public function testReturnsTheExistingCompanyWhenOneMatches(): void
    {
        $existing = new Company();
        $existing->setName('Acme Corp');

        $companyRepository = $this->createStub(CompanyRepository::class);
        $companyRepository->method('findOneByNameIgnoreCase')->willReturn($existing);

        $resolver = new CompanyResolver($companyRepository);

        self::assertSame($existing, $resolver->findOrCreateByName('acme corp'));
    }

    public function testCreatesANewUnpersistedCompanyWhenNoneMatches(): void
    {
        $companyRepository = $this->createStub(CompanyRepository::class);
        $companyRepository->method('findOneByNameIgnoreCase')->willReturn(null);

        $resolver = new CompanyResolver($companyRepository);
        $company = $resolver->findOrCreateByName('Brand New Co');

        self::assertSame('Brand New Co', $company->getName());
        self::assertNull($company->getId(), 'a newly created company should not be persisted by the resolver itself');
    }

    public function testNameIsTrimmedBeforeLookupAndBeforeCreating(): void
    {
        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->expects(self::once())
            ->method('findOneByNameIgnoreCase')
            ->with('Padded Co')
            ->willReturn(null);

        $resolver = new CompanyResolver($companyRepository);
        $company = $resolver->findOrCreateByName('  Padded Co  ');

        self::assertSame('Padded Co', $company->getName());
    }
}
