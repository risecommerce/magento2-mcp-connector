<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Builtin\ApiKeyDetector;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRule;
use Risecommerce\PiiRedactor\Detector\Secret\SecretRules;
use PHPUnit\Framework\TestCase;

final class ApiKeyDetectorTest extends TestCase
{
    public function testDetectsKnownVendorFormats(): void
    {
        $d = ApiKeyDetector::create();

        $cases = [
            ['sk_live_' . str_repeat('a1B2', 6), 0.95],                     // 24-char tail
            ['AKIA' . str_repeat('EXAMPLE0', 2), 0.9],                      // 16-char tail
            ['ghp_' . str_repeat('x9', 18), 0.95],                          // 36-char tail
            ['eyJ' . str_repeat('a', 12) . '.eyJ' . str_repeat('b', 12) . '.' . str_repeat('c', 16), 0.85],
        ];
        foreach ($cases as [$token, $score]) {
            $matches = $d->detect('key ' . $token . ' end');
            self::assertCount(1, $matches, $token);
            self::assertSame($score, $matches[0]->score, $token);
            self::assertSame('API_KEY', $matches[0]->entityType, $token);
        }
    }

    public function testIgnoresTooShortAndMidIdentifierCandidates(): void
    {
        $d = ApiKeyDetector::create();

        self::assertSame([], $d->detect('short sk_live_abc end'));
        self::assertSame([], $d->detect('word risky_business here'));
        self::assertSame([], $d->detect('id xsk_live_' . str_repeat('a1B2', 6)));
    }

    public function testConsumerRulesExtendTheDefaultPack(): void
    {
        $rules = SecretRules::default()->with(new SecretRule('acme-internal', 'acme_', 'a-f0-9', 32, 32, 0.9));
        $d = ApiKeyDetector::create([], $rules);

        $matches = $d->detect('token acme_' . str_repeat('ab12', 8) . ' end');

        self::assertCount(1, $matches);
        self::assertStringContainsString('acme-internal', (string)$matches[0]->explanation);
    }
}
