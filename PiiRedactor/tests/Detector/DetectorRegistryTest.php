<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector;

use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use PHPUnit\Framework\TestCase;

final class DetectorRegistryTest extends TestCase
{
    private function detector(string $name): PatternDetector
    {
        return new PatternDetector($name, 'NUMBER', [new Pattern('p', '/\d+/u', 0.5)]);
    }

    public function testRegisterHasRemove(): void
    {
        $registry = new DetectorRegistry();
        $registry->register($this->detector('a'));

        self::assertTrue($registry->has('a'));
        self::assertCount(1, $registry->all());

        $registry->remove('a');

        self::assertFalse($registry->has('a'));
        self::assertSame([], $registry->all());
    }

    public function testRegisteringSameNameReplaces(): void
    {
        $registry = new DetectorRegistry();
        $registry->register($this->detector('a'));
        $registry->register($this->detector('a'));

        self::assertCount(1, $registry->all());
    }
}
