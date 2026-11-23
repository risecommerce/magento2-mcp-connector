<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests;

use InvalidArgumentException;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\PiiMatch;
use PHPUnit\Framework\TestCase;

final class PiiMatchTest extends TestCase
{
    public function testExposesConstructorValues(): void
    {
        $match = new PiiMatch(EntityType::EMAIL->value, 5, 20, 0.9, 'email');

        self::assertSame('EMAIL', $match->entityType);
        self::assertSame(5, $match->start);
        self::assertSame(20, $match->end);
        self::assertSame(0.9, $match->score);
        self::assertSame('email', $match->detectorName);
        self::assertNull($match->explanation);
        self::assertSame(15, $match->length());
    }

    public function testRejectsInvalidOffsets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PiiMatch('EMAIL', 10, 5, 0.9, 'email');
    }

    public function testRejectsOutOfRangeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PiiMatch('EMAIL', 0, 5, 1.5, 'email');
    }

    public function testOverlapsAndContains(): void
    {
        $a = new PiiMatch('EMAIL', 0, 10, 0.9, 'email');
        $b = new PiiMatch('URL', 5, 15, 0.6, 'url');
        $c = new PiiMatch('URL', 10, 15, 0.6, 'url');
        $inner = new PiiMatch('EMAIL', 2, 8, 0.9, 'email');

        self::assertTrue($a->overlaps($b));
        self::assertTrue($b->overlaps($a));
        self::assertFalse($a->overlaps($c)); // touching, not overlapping
        self::assertTrue($a->contains($inner));
        self::assertFalse($inner->contains($a));
    }

    public function testWithScoreReturnsNewInstance(): void
    {
        $match = new PiiMatch('PHONE', 0, 5, 0.3, 'phone', 'pattern "intl"');
        $boosted = $match->withScore(0.65, 'pattern "intl", context "tel"');

        self::assertSame(0.3, $match->score);
        self::assertSame(0.65, $boosted->score);
        self::assertSame('pattern "intl", context "tel"', $boosted->explanation);
    }

    public function testWithOffsetShiftsBothEnds(): void
    {
        $match = new PiiMatch('PHONE', 5, 10, 0.5, 'phone');
        $shifted = $match->withOffset(100);

        self::assertSame(105, $shifted->start);
        self::assertSame(110, $shifted->end);
    }

    public function testScoreBoundariesAreAccepted(): void
    {
        $lo = new PiiMatch('EMAIL', 0, 5, 0.0, 'email');
        $hi = new PiiMatch('EMAIL', 0, 5, 1.0, 'email');

        self::assertSame(0.0, $lo->score);
        self::assertSame(1.0, $hi->score);
    }

    public function testWithScoreKeepsExplanationWhenNullPassed(): void
    {
        $match = new PiiMatch('PHONE', 0, 5, 0.3, 'phone', 'pattern "intl"');

        self::assertSame('pattern "intl"', $match->withScore(0.5)->explanation);
    }

    public function testWithOffsetNegativeDelta(): void
    {
        $match = new PiiMatch('PHONE', 5, 10, 0.5, 'phone');
        $shifted = $match->withOffset(-3);

        self::assertSame(2, $shifted->start);
        self::assertSame(7, $shifted->end);

        $this->expectException(InvalidArgumentException::class);
        $match->withOffset(-6);
    }
}
