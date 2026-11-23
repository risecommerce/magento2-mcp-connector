<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector\Secret;

use Risecommerce\PiiRedactor\Detector\Secret\SecretRule;
use PHPUnit\Framework\TestCase;

final class SecretRuleTest extends TestCase
{
    public function testDerivesRegexFromPrefixCharsetAndLengths(): void
    {
        $rule = new SecretRule('acme', 'acme_', 'a-f0-9', 32, 32, 0.9);

        self::assertSame('/\bacme_[a-f0-9]{32,32}\b/u', $rule->regex);
        self::assertSame(1, preg_match($rule->regex, 'x acme_' . str_repeat('ab12', 8) . ' y'));
        self::assertSame(0, preg_match($rule->regex, 'x acme_' . str_repeat('ab12', 4) . ' y')); // too short
        self::assertSame(0, preg_match($rule->regex, 'xacme_' . str_repeat('ab12', 8))); // mid-identifier
    }

    public function testNullMaxLengthMeansUnbounded(): void
    {
        $rule = new SecretRule('acme', 'acme_', 'a-z', 4);

        self::assertSame('/\bacme_[a-z]{4,}\b/u', $rule->regex);
    }

    public function testFromRegexOverridesDerivation(): void
    {
        $rule = SecretRule::fromRegex('custom', '/\bzz[0-9]{4}\b/u', 'zz', 0.7);

        self::assertSame('/\bzz[0-9]{4}\b/u', $rule->regex);
        self::assertSame('zz', $rule->prefix);
        self::assertSame(0.7, $rule->baseScore);
    }

    public function testToPatternCarriesIdScoreAndPrefixNeedle(): void
    {
        $pattern = (new SecretRule('acme', 'acme_', 'a-f0-9', 32, 32, 0.9))->toPattern();

        self::assertSame('acme', $pattern->name);
        self::assertSame(0.9, $pattern->baseScore);
        self::assertSame(['acme_'], $pattern->requiredNeedles);
    }
}
