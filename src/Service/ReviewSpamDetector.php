<?php

namespace App\Service;

use App\Entity\Company;
use App\Repository\ReviewRepository;

final class ReviewSpamDetector
{
    private const array BANNED_WORDS = [
        'click here',
        'fuck',
        'shit',
        'bitch',
        'casino'
        // add more banned words as needed
    ];

    private const float MAX_CAPS_RATIO = 0.7;
    private const int MIN_LENGTH_FOR_CAPS_CHECK = 20;
    private const string DUPLICATE_WINDOW = 'PT1H';

    public function __construct(private readonly ReviewRepository $reviewRepository) {}

    public function isSpam(string $reviewText, string $authorEmail, Company $company): bool
    {
        return $this->containsBannedWord($reviewText)
            || $this->hasExcessiveCaps($reviewText)
            || $this->containsLink($reviewText)
            || $this->isDuplicateSubmission($authorEmail, $company);
    }

    private function containsBannedWord(string $text): bool
    {
        $lowerText = mb_strtolower($text);

        foreach (self::BANNED_WORDS as $word) {
            if (str_contains($lowerText, $word)) {
                return true;
            }
        }

        return false;
    }

    private function hasExcessiveCaps(string $text): bool
    {
        $letters = preg_replace('/[^a-zA-Z]/', '', $text);

        if (strlen($letters) < self::MIN_LENGTH_FOR_CAPS_CHECK) {
            return false;
        }

        $upperLetters = preg_replace('/[^A-Z]/', '', $letters);

        return (strlen($upperLetters) / strlen($letters)) > self::MAX_CAPS_RATIO;
    }

    private function containsLink(string $text): bool
    {
        return (bool) preg_match('#https?://|www\.#i', $text);
    }

    private function isDuplicateSubmission(string $authorEmail, Company $company): bool
    {
        if ($company->getId() === null) {
            return false;
        }

        return $this->reviewRepository->hasRecentSubmission(
            $authorEmail,
            $company,
            new \DateInterval(self::DUPLICATE_WINDOW),
        );
    }
}
