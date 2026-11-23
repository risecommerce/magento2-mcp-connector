<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Secret;

use Risecommerce\PiiRedactor\Detector\Secret\SecretRule;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRules;
use PHPUnit\Framework\TestCase;

final class SecretRulesTest extends TestCase
{
    public function testDefaultPackShipsKnownVendorsWithUniqueIds(): void
    {
        $ids = array_map(static fn (SecretRule $r): string => $r->id, SecretRules::default()->all());

        self::assertContains('stripe-secret-live', $ids);
        self::assertContains('aws-access-key-id', $ids);
        self::assertContains('github-token', $ids);
        self::assertContains('jwt', $ids);
        self::assertSame($ids, array_unique($ids));
    }

    public function testNoneIsEmpty(): void
    {
        self::assertSame([], SecretRules::none()->all());
    }

    public function testWithReturnsExtendedCopy(): void
    {
        $base = SecretRules::none();
        $extended = $base->with(new SecretRule('acme', 'acme_', 'a-f0-9', 32, 32));

        self::assertSame([], $base->all()); // receiver unchanged
        self::assertCount(1, $extended->all());
    }

    public function testToPatternsCompilesEveryRule(): void
    {
        $rules = SecretRules::default();

        self::assertCount(count($rules->all()), $rules->toPatterns());
    }
}
