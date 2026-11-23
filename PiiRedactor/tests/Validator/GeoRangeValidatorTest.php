<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Validator;

use Risecommerce\PiiRedactor\Validator\GeoRangeValidator;
use PHPUnit\Framework\TestCase;

final class GeoRangeValidatorTest extends TestCase
{
    public function testInRangePairKeepsBaseScore(): void
    {
        self::assertNull((new GeoRangeValidator())->validate('56.9496, 24.1052'));
        self::assertNull((new GeoRangeValidator())->validate('-89.9999,-179.9999'));
    }

    public function testOutOfRangeDiscards(): void
    {
        self::assertFalse((new GeoRangeValidator())->validate('95.1234, 24.1052'));   // |lat| > 90
        self::assertFalse((new GeoRangeValidator())->validate('56.9496, 181.0001'));  // |long| > 180
    }

    public function testMalformedCandidateDiscards(): void
    {
        self::assertFalse((new GeoRangeValidator())->validate('56.9496'));
    }
}
