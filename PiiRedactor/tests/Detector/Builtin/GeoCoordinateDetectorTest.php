<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Builtin\GeoCoordinateDetector;
use PHPUnit\Framework\TestCase;

final class GeoCoordinateDetectorTest extends TestCase
{
    public function testDetectsDecimalPairAtLowBaseScore(): void
    {
        $matches = GeoCoordinateDetector::create()->detect('gps 56.9496, 24.1052 here');

        self::assertCount(1, $matches);
        self::assertSame(0.3, $matches[0]->score); // context boost happens in Analyzer
        self::assertSame('GEO_COORDINATE', $matches[0]->entityType);
    }

    public function testIgnoresPricesOutOfRangeAndEmbeddedNumbers(): void
    {
        $d = GeoCoordinateDetector::create();

        self::assertSame([], $d->detect('totals 12.99, 24.50 eur'));         // < 3 decimals
        self::assertSame([], $d->detect('pair 95.1234, 24.1052 invalid'));   // validator discards
        self::assertSame([], $d->detect('release 1.2.3456, 6.7890123 tag')); // digit/dot lookbehind guard
    }
}
