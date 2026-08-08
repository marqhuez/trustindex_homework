<?php

namespace App\Model;

use App\Entity\Company;

final readonly class CompanyStats
{
    public function __construct(
        public Company $company,
        public int $reviewCount,
        public ?float $averageRating,
    ) {
    }
}
