<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Strategy;

use Risecommerce\PiiRedactor\PiiMatch;
use Risecommerce\PiiRedactor\Strategy\ReplaceStrategy;
use PHPUnit\Framework\TestCase;

final class ReplaceStrategyTest extends TestCase
{
    public function testDefaultTemplateUsesEntityType(): void
    {
        $match = new PiiMatch('EMAIL', 0, 16, 0.9, 'email');

        self::assertSame('[EMAIL]', (new ReplaceStrategy())->redact($match, 'john@example.com'));
    }

    public function testCustomTemplate(): void
    {
        $match = new PiiMatch('PHONE', 0, 8, 0.7, 'phone');

        self::assertSame('<REDACTED:PHONE>', (new ReplaceStrategy('<REDACTED:{type}>'))->redact($match, '26123456'));
        self::assertSame('***', (new ReplaceStrategy('***'))->redact($match, '26123456'));
    }
}
