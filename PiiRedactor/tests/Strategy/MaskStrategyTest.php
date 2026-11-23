<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Strategy;

use Risecommerce\PiiRedactor\PiiMatch;
use Risecommerce\PiiRedactor\Strategy\MaskStrategy;
use PHPUnit\Framework\TestCase;

final class MaskStrategyTest extends TestCase
{
    private function match(string $original): PiiMatch
    {
        return new PiiMatch('CREDIT_CARD', 0, strlen($original), 1.0, 'credit-card');
    }

    public function testFullMaskByDefault(): void
    {
        self::assertSame('********', (new MaskStrategy())->redact($this->match('26123456'), '26123456'));
    }

    public function testKeepLastFour(): void
    {
        $strategy = new MaskStrategy('*', 4, true);

        self::assertSame('************1111', $strategy->redact($this->match('4111111111111111'), '4111111111111111'));
    }

    public function testKeepFirstTwoFromStart(): void
    {
        $strategy = new MaskStrategy('#', 2, false);

        self::assertSame('jo##############', $strategy->redact($this->match('john@example.com'), 'john@example.com'));
    }

    public function testUnmaskedCountLargerThanValueMasksNothing(): void
    {
        $strategy = new MaskStrategy('*', 10, true);

        self::assertSame('abc', $strategy->redact($this->match('abc'), 'abc'));
    }

    public function testMultibyteOriginalIsMaskedPerCharacter(): void
    {
        $strategy = new MaskStrategy('*', 2, true);

        self::assertSame('**ņš', $strategy->redact($this->match('Jāņš'), 'Jāņš'));
    }
}
