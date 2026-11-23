<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Builtin\SwiftBicDetector;
use PHPUnit\Framework\TestCase;

final class SwiftBicDetectorTest extends TestCase
{
    public function testDetectsEightAndElevenCharBics(): void
    {
        $d = SwiftBicDetector::create();

        foreach (['HABALV22', 'DEUTDEFF500'] as $bic) {
            $matches = $d->detect('code ' . $bic . ' end');
            self::assertCount(1, $matches, $bic);
            self::assertSame(0.45, $matches[0]->score, $bic);
            self::assertSame('SWIFT_BIC', $matches[0]->entityType, $bic);
        }
    }

    public function testDiscardsAllCapsWordsWithoutValidCountry(): void
    {
        $d = SwiftBicDetector::create();

        self::assertSame([], $d->detect('my PASSWORD here'));
        self::assertSame([], $d->detect('wire TRANSFER now'));
        self::assertSame([], $d->detect('id HABALV22XYZa mixed')); // lowercase tail breaks the word boundary
    }
}
