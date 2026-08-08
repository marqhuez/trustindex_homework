<?php

namespace App\Tests\Service;

use App\Entity\Company;
use App\Repository\ReviewRepository;
use App\Service\ReviewSpamDetector;
use PHPUnit\Framework\TestCase;

final class ReviewSpamDetectorTest extends TestCase
{
    public function testCleanReviewIsNotSpam(): void
    {
        $detector = $this->detectorWithNoRecentSubmissions();

        self::assertFalse($detector->isSpam(
            'Friendly staff and the order arrived earlier than expected.',
            'happy.customer@example.com',
            new Company(),
        ));
    }

    public function testBannedWordIsFlaggedAsSpam(): void
    {
        $detector = $this->detectorWithNoRecentSubmissions();

        self::assertTrue($detector->isSpam(
            'Best CASINO bonuses ever, come play now!',
            'spammer@example.com',
            new Company(),
        ));
    }

    public function testExcessiveCapsIsFlaggedAsSpam(): void
    {
        $detector = $this->detectorWithNoRecentSubmissions();

        self::assertTrue($detector->isSpam(
            'THIS COMPANY IS ABSOLUTELY AMAZING BUY NOW WHILE IT LASTS',
            'shouty@example.com',
            new Company(),
        ));
    }

    public function testShortAllCapsTextIsNotFlagged(): void
    {
        // a short, enthusiastic review shouldn't trip the caps check just for being brief
        $detector = $this->detectorWithNoRecentSubmissions();

        self::assertFalse($detector->isSpam(
            'GREAT!',
            'enthusiastic@example.com',
            new Company(),
        ));
    }

    public function testLinkInReviewTextIsFlaggedAsSpam(): void
    {
        $detector = $this->detectorWithNoRecentSubmissions();

        self::assertTrue($detector->isSpam(
            'Loved it, check out my blog at https://example.com/promo for a discount.',
            'linker@example.com',
            new Company(),
        ));
    }

    public function testRecentDuplicateSubmissionIsFlaggedAsSpam(): void
    {
        $reviewRepository = $this->createStub(ReviewRepository::class);
        $reviewRepository->method('hasRecentSubmission')->willReturn(true);

        $detector = new ReviewSpamDetector($reviewRepository);

        self::assertTrue($detector->isSpam(
            'Another completely normal review, nothing suspicious here.',
            'repeat.poster@example.com',
            new Company(),
        ));
    }

    private function detectorWithNoRecentSubmissions(): ReviewSpamDetector
    {
        $reviewRepository = $this->createStub(ReviewRepository::class);
        $reviewRepository->method('hasRecentSubmission')->willReturn(false);

        return new ReviewSpamDetector($reviewRepository);
    }
}
