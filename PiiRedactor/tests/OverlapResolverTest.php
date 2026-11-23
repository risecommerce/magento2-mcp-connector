<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests;

use Risecommerce\PiiRedactor\OverlapResolver;
use Risecommerce\PiiRedactor\PiiMatch;
use PHPUnit\Framework\TestCase;

final class OverlapResolverTest extends TestCase
{
    private OverlapResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OverlapResolver();
    }

    public function testHigherScoreWins(): void
    {
        $email = new PiiMatch('EMAIL', 0, 20, 0.9, 'email');
        $url = new PiiMatch('URL', 5, 20, 0.6, 'url');

        $resolved = $this->resolver->resolve([$url, $email]);

        self::assertCount(1, $resolved);
        self::assertSame('EMAIL', $resolved[0]->entityType);
    }

    public function testLongerSpanWinsOnScoreTie(): void
    {
        $short = new PiiMatch('PHONE', 0, 8, 0.7, 'phone');
        $long = new PiiMatch('PHONE', 0, 12, 0.7, 'phone');

        $resolved = $this->resolver->resolve([$short, $long]);

        self::assertCount(1, $resolved);
        self::assertSame(12, $resolved[0]->end);
    }

    public function testIdenticalSpansMergeExplanations(): void
    {
        $a = new PiiMatch('PHONE', 0, 8, 0.7, 'phone-intl', 'pattern "intl"');
        $b = new PiiMatch('PHONE', 0, 8, 0.7, 'phone-national', 'pattern "national"');

        $resolved = $this->resolver->resolve([$a, $b]);

        self::assertCount(1, $resolved);
        self::assertStringContainsString('pattern "intl"', (string)$resolved[0]->explanation);
        self::assertStringContainsString('pattern "national"', (string)$resolved[0]->explanation);
    }

    public function testNonOverlappingMatchesAllSurviveSortedByStart(): void
    {
        $b = new PiiMatch('PHONE', 20, 28, 0.7, 'phone');
        $a = new PiiMatch('EMAIL', 0, 10, 0.9, 'email');

        $resolved = $this->resolver->resolve([$b, $a]);

        self::assertCount(2, $resolved);
        self::assertSame(0, $resolved[0]->start);
        self::assertSame(20, $resolved[1]->start);
    }

    public function testContainedSpanIsAbsorbed(): void
    {
        $outer = new PiiMatch('URL', 0, 30, 0.9, 'url');
        $inner = new PiiMatch('IP_ADDRESS', 10, 20, 0.6, 'ip');

        $resolved = $this->resolver->resolve([$inner, $outer]);

        self::assertCount(1, $resolved);
        self::assertSame('URL', $resolved[0]->entityType);
    }
}
