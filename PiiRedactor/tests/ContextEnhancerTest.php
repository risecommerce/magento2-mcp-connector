<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests;

use Risecommerce\PiiRedactor\ContextEnhancer;
use Risecommerce\PiiRedactor\PiiMatch;
use PHPUnit\Framework\TestCase;

final class ContextEnhancerTest extends TestCase
{
    private ContextEnhancer $enhancer;

    protected function setUp(): void
    {
        $this->enhancer = new ContextEnhancer();
    }

    private function matchIn(string $text, string $needle, float $score): PiiMatch
    {
        $start = strpos($text, $needle);
        \assert($start !== false);

        return new PiiMatch('PHONE', $start, $start + strlen($needle), $score, 'phone', 'pattern "national"');
    }

    public function testBoostsWhenContextWordPrecedesMatch(): void
    {
        $text = 'Please call my phone 26123456 today';
        $match = $this->matchIn($text, '26123456', 0.3);

        $enhanced = $this->enhancer->enhance($text, $match, ['phone', 'tel']);

        self::assertEqualsWithDelta(0.65, $enhanced->score, 0.001);
        self::assertStringContainsString('context "phone"', (string)$enhanced->explanation);
    }

    public function testNoBoostWithoutContextWord(): void
    {
        $text = 'Reference 26123456 attached';
        $match = $this->matchIn($text, '26123456', 0.3);

        self::assertSame(0.3, $this->enhancer->enhance($text, $match, ['phone'])->score);
    }

    public function testContextIsCaseInsensitive(): void
    {
        $text = 'PHONE: 26123456';
        $match = $this->matchIn($text, '26123456', 0.3);

        self::assertGreaterThan(0.3, $this->enhancer->enhance($text, $match, ['phone'])->score);
    }

    public function testSentenceBoundaryCutsWindow(): void
    {
        // "phone" is within 40 bytes but in the previous sentence
        $text = 'I lost my phone. Order 26123456 shipped';
        $match = $this->matchIn($text, '26123456', 0.3);

        self::assertSame(0.3, $this->enhancer->enhance($text, $match, ['phone'])->score);
    }

    public function testContextWordOutsideWindowDoesNotBoost(): void
    {
        $text = 'phone' . str_repeat(' x', 30) . ' 26123456';
        $match = $this->matchIn($text, '26123456', 0.3);

        self::assertSame(0.3, $this->enhancer->enhance($text, $match, ['phone'])->score);
    }

    public function testBoostIsFlooredAndCapped(): void
    {
        $text = 'phone 26123456';

        $low = $this->matchIn($text, '26123456', 0.02);
        self::assertEqualsWithDelta(0.4, $this->enhancer->enhance($text, $low, ['phone'])->score, 0.001);

        $high = $this->matchIn($text, '26123456', 0.9);
        self::assertSame(1.0, $this->enhancer->enhance($text, $high, ['phone'])->score);
    }

    public function testExplanationWithoutPriorExplanationHasNoLeadingComma(): void
    {
        $text = 'phone 26123456';
        $start = strpos($text, '26123456');
        \assert($start !== false);
        $match = new PiiMatch('PHONE', $start, $start + 8, 0.3, 'phone');

        $enhanced = $this->enhancer->enhance($text, $match, ['phone']);

        self::assertSame('context "phone" (+0.35)', $enhanced->explanation);
    }

    public function testAlreadyMaximalScoreIsUntouched(): void
    {
        $text = 'phone 26123456';
        $match = $this->matchIn($text, '26123456', 1.0);

        self::assertSame($match, $this->enhancer->enhance($text, $match, ['phone']));
    }

    public function testNullEnhancerNeverBoosts(): void
    {
        $text = 'phone 26123456';
        $match = $this->matchIn($text, '26123456', 0.3);

        $enhanced = (new \Risecommerce\PiiRedactor\NullContextEnhancer())->enhance($text, $match, ['phone']);

        self::assertSame($match, $enhanced);
        self::assertSame(0.3, $enhanced->score);
    }
}
