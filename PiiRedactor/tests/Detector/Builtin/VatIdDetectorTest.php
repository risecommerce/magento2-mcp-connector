<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Builtin\VatIdDetector;
use PHPUnit\Framework\TestCase;

final class VatIdDetectorTest extends TestCase
{
    public function testDetectsPerCountryShapes(): void
    {
        $d = VatIdDetector::create();

        $valid = [
            'LV' . str_repeat('4', 11),   // numeric group
            'DE' . str_repeat('1', 9),
            'ATU' . str_repeat('2', 8),   // special group
            'NL' . str_repeat('3', 9) . 'B12',
            'IE1234567FA',
        ];
        foreach ($valid as $vat) {
            $matches = $d->detect('vat ' . $vat . ' end');
            self::assertCount(1, $matches, $vat);
            self::assertSame(0.6, $matches[0]->score, $vat);
            self::assertSame('VAT_ID', $matches[0]->entityType, $vat);
        }
    }

    public function testToleratesOneSpaceAfterCountryCode(): void
    {
        $matches = VatIdDetector::create()->detect('reg LV ' . str_repeat('4', 11) . ' end');

        self::assertCount(1, $matches);
    }

    public function testIgnoresWrongLengthsAndPlainWords(): void
    {
        $d = VatIdDetector::create();

        self::assertSame([], $d->detect('code LV1234 end'));                    // too short for LV
        self::assertSame([], $d->detect('id DE' . str_repeat('1', 12) . ' x')); // too long for DE
        self::assertSame([], $d->detect('IT department meeting'));              // no digits
    }
}
