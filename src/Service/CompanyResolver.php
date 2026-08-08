<?php

namespace App\Service;

use App\Entity\Company;
use App\Repository\CompanyRepository;

final class CompanyResolver
{
    public function __construct(private readonly CompanyRepository $companyRepository) {}

    public function findOrCreateByName(string $name): Company
    {
        $name = trim($name);

        return $this->companyRepository->findOneByNameIgnoreCase($name)
            ?? (new Company())->setName($name);
    }
}
