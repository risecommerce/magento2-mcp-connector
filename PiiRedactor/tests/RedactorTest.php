<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests;

use Risecommerce\PiiRedactor\AnalysisResult;
use Risecommerce\PiiRedactor\PiiMatch;
use Risecommerce\PiiRedactor\RedactionConfig;
use Risecommerce\PiiRedactor\Redactor;
use Risecommerce\PiiRedactor\Strategy\MaskStrategy;
use PHPUnit\Framework\TestCase;

final class RedactorTest extends TestCase
{
    private Redactor $redactor;

    protected function setUp(): void
    {
        $this->redactor = new Redactor();
    }

    public function testReplacesWithDefaultPlaceholders(): void
    {
        $text = 'Mail john@example.com or call 26123456';
        $result = $this->redactor->redact($text, new AnalysisResult([
            new PiiMatch('EMAIL', 5, 21, 0.9, 'email'),
            new PiiMatch('PHONE', 30, 38, 0.65, 'phone'),
        ]));

        self::assertSame('Mail [EMAIL] or call [PHONE]', $result->text());
    }

    public function testPerEntityStrategyOverride(): void
    {
        $text = 'Card 4111111111111111 used';
        $config = new RedactionConfig(['CREDIT_CARD' => new MaskStrategy('*', 4, true)]);

        $result = $this->redactor->redact($text, new AnalysisResult([
            new PiiMatch('CREDIT_CARD', 5, 21, 1.0, 'credit-card'),
        ]), $config);

        self::assertSame('Card ************1111 used', $result->text());
    }

    public function testAuditTrailRecordsShiftedOffsets(): void
    {
        $text = 'Mail john@example.com or call 26123456';
        $result = $this->redactor->redact($text, new AnalysisResult([
            new PiiMatch('EMAIL', 5, 21, 0.9, 'email'),
            new PiiMatch('PHONE', 30, 38, 0.65, 'phone'),
        ]));

        $replacements = $result->replacements();
        self::assertCount(2, $replacements);

        self::assertSame('EMAIL', $replacements[0]->entityType);
        self::assertSame(5, $replacements[0]->start);
        self::assertSame(21, $replacements[0]->end);
        self::assertSame('[EMAIL]', $replacements[0]->replacement);
        self::assertSame(5, $replacements[0]->newStart);
        self::assertSame(12, $replacements[0]->newEnd);

        // '[EMAIL]' (7) replaced 16 bytes => delta -9; phone started at 30
        self::assertSame(21, $replacements[1]->newStart);
        self::assertSame('[PHONE]', substr($result->text(), $replacements[1]->newStart, 7));
    }

    public function testUnsortedMatchesAreHandled(): void
    {
        $text = 'Mail john@example.com or call 26123456';
        $result = $this->redactor->redact($text, new AnalysisResult([
            new PiiMatch('PHONE', 30, 38, 0.65, 'phone'),
            new PiiMatch('EMAIL', 5, 21, 0.9, 'email'),
        ]));

        self::assertSame('Mail [EMAIL] or call [PHONE]', $result->text());
    }

    public function testOverlappingMatchesDoNotCorruptOutput(): void
    {
        $text = 'Mail john@example.com today';
        $result = $this->redactor->redact($text, new AnalysisResult([
            new PiiMatch('EMAIL', 5, 21, 0.9, 'email'),
            new PiiMatch('URL', 10, 25, 0.6, 'url'), // overlaps the email span
        ]));

        self::assertSame('Mail [EMAIL] today', $result->text());
        self::assertCount(1, $result->replacements());
    }

    public function testEmptyAnalysisReturnsOriginalText(): void
    {
        $result = $this->redactor->redact('nothing here', new AnalysisResult([]));

        self::assertSame('nothing here', $result->text());
        self::assertSame([], $result->replacements());
    }

    public function testMultibyteTextSurvivesRedaction(): void
    {
        $text = 'Lūdzu raksti jānis@piemērs.lv šodien';
        $start = strpos($text, 'jānis@');
        \assert($start !== false);
        $end = $start + strlen('jānis@piemērs.lv');

        $result = $this->redactor->redact($text, new AnalysisResult([
            new PiiMatch('EMAIL', $start, $end, 0.9, 'email'),
        ]));

        self::assertSame('Lūdzu raksti [EMAIL] šodien', $result->text());
    }
}
