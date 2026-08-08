<?php

namespace App\Form\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReviewRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $companyName = '';

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    public ?int $rating = null;

    #[Assert\NotBlank]
    public string $reviewText = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $authorEmail = '';
}
