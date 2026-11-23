<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\Exception\PiiRedactorException;
use Risecommerce\PiiRedactor\Validator\LuhnValidator;
use PHPUnit\Framework\TestCase;

final class PatternDetectorTest extends TestCase
{
    public function testFindsMatchesWithByteOffsets(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.5),
        ]);

        $matches = $detector->detect('abc 123 def 456');

        self::assertCount(2, $matches);
        self::assertSame(4, $matches[0]->start);
        self::assertSame(7, $matches[0]->end);
        self::assertSame(0.5, $matches[0]->score);
        self::assertSame('NUMBER', $matches[0]->entityType);
        self::assertSame('digits', $matches[0]->detectorName);
        self::assertSame(12, $matches[1]->start);
    }

    public function testMultibyteTextProducesCorrectByteOffsets(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.5),
        ]);

        // "āē" is 4 bytes in UTF-8; match starts at byte 5
        $matches = $detector->detect('āē 123');

        self::assertSame(5, $matches[0]->start);
        self::assertSame('123', substr('āē 123', $matches[0]->start, $matches[0]->length()));
    }

    public function testValidatorTrueForcesScoreToOne(): void
    {
        $detector = new PatternDetector('card', 'CREDIT_CARD', [
            new Pattern('digit-run', '/(?:\d[ -]?){12,18}\d/u', 0.3),
        ], new LuhnValidator(), [], ['-' => '', ' ' => '']);

        $matches = $detector->detect('Card: 4111 1111 1111 1111');

        self::assertCount(1, $matches);
        self::assertSame(1.0, $matches[0]->score);
    }

    public function testValidatorFalseDiscardsMatch(): void
    {
        $detector = new PatternDetector('card', 'CREDIT_CARD', [
            new Pattern('digit-run', '/(?:\d[ -]?){12,18}\d/u', 0.3),
        ], new LuhnValidator(), [], ['-' => '', ' ' => '']);

        self::assertSame([], $detector->detect('Order no: 4111 1111 1111 1112'));
    }

    public function testNoValidatorKeepsBaseScore(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.42),
        ]);

        self::assertSame(0.42, $detector->detect('123')[0]->score);
    }

    public function testInvalidRegexThrows(): void
    {
        $detector = new PatternDetector('broken', 'NUMBER', [
            new Pattern('backtrack', '/(?:a+)+$/u', 0.5),
        ]);

        $prev = ini_set('pcre.backtrack_limit', '100');
        try {
            $this->expectException(PiiRedactorException::class);
            $detector->detect(str_repeat('a', 10000) . 'b');
        } finally {
            ini_set('pcre.backtrack_limit', $prev !== false ? $prev : '1000000');
        }
    }

    public function testRequiredNeedlePresentStillMatches(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.5, ['#']),
        ]);

        $matches = $detector->detect('id #123');

        self::assertCount(1, $matches);
        self::assertSame(4, $matches[0]->start);
    }

    public function testAbsentRequiredNeedleSkipsRegex(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.5, ['#']),
        ]);

        // The text matches the regex but lacks the needle '#', so it must be skipped.
        self::assertSame([], $detector->detect('plain 123 text'));
    }

    public function testAnyOfMultipleNeedlesEnablesMatch(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.5, ['@', '#']),
        ]);

        self::assertCount(1, $detector->detect('user@host 123'));
        self::assertSame([], $detector->detect('plain 123'));
    }

    public function testExposesMetadata(): void
    {
        $detector = new PatternDetector('digits', 'NUMBER', [
            new Pattern('three-digits', '/\d{3}/u', 0.5),
        ], null, ['number', 'code']);

        self::assertSame('digits', $detector->getName());
        self::assertSame(['NUMBER'], $detector->getSupportedEntityTypes());
        self::assertSame(['number', 'code'], $detector->getContextWords());
    }
}
