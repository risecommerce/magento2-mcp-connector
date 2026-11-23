<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests;

use Risecommerce\PiiRedactor\AnalyzerOptions;
use Risecommerce\PiiRedactor\Context\ContextWords;
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\PiiRedactor;
use Risecommerce\PiiRedactor\RedactionConfig;
use Risecommerce\PiiRedactor\Strategy\MaskStrategy;
use PHPUnit\Framework\TestCase;

final class PiiRedactorTest extends TestCase
{
    public function testOneLinerRedactsMixedDocument(): void
    {
        $text = implode(' ', [
            'Customer john.doe@example.com,',
            'phone +371 26123456,',
            'card 4111 1111 1111 1111,',
            'IBAN LV80BANK0000435195001,',
            'logged in from 192.168.1.100.',
        ]);

        $redacted = (new PiiRedactor())->redact($text)->text();

        self::assertStringNotContainsString('john.doe@example.com', $redacted);
        self::assertStringNotContainsString('26123456', $redacted);
        self::assertStringNotContainsString('4111', $redacted);
        self::assertStringNotContainsString('LV80BANK', $redacted);
        self::assertStringNotContainsString('192.168.1.100', $redacted);
        self::assertStringContainsString('[EMAIL]', $redacted);
        self::assertStringContainsString('[PHONE]', $redacted);
        self::assertStringContainsString('[CREDIT_CARD]', $redacted);
        self::assertStringContainsString('[IBAN]', $redacted);
        self::assertStringContainsString('[IP_ADDRESS]', $redacted);
    }

    public function testAnalyzeOnlyMode(): void
    {
        $result = (new PiiRedactor())->analyze('Mail john@example.com today');

        self::assertSame(1, $result->count());
        self::assertSame('EMAIL', $result->matches()[0]->entityType);
    }

    public function testCleanTextPassesThroughUntouched(): void
    {
        $text = 'The quick brown fox jumps over the lazy dog on 2026-06-10.';

        self::assertSame($text, (new PiiRedactor())->redact($text)->text());
    }

    public function testCustomConfigAndOptions(): void
    {
        $redactor = new PiiRedactor(
            DetectorRegistry::withDefaults(),
            new RedactionConfig(['CREDIT_CARD' => new MaskStrategy('*', 4, true)]),
            new AnalyzerOptions(entityTypes: ['CREDIT_CARD']),
        );

        $result = $redactor->redact('Mail a@b.io, card 4111111111111111.');

        self::assertStringContainsString('a@b.io', $result->text()); // email type filtered out
        self::assertStringContainsString('************1111', $result->text());
    }

    public function testVersionStringIsNotRedactedAsIp(): void
    {
        $text = 'Upgraded module to version 1.2.3.4.5 yesterday';

        self::assertSame($text, (new PiiRedactor())->redact($text)->text());

        $alpha = 'Upgraded module to version 1.2.3.4.beta yesterday';
        self::assertSame($alpha, (new PiiRedactor())->redact($alpha)->text());
    }

    public function testConsumerExtendedContextWordsBoostNationalPhone(): void
    {
        // "tālrunis" is a Latvian word the library does NOT ship; the bare national
        // number only crosses threshold once the consumer adds it to the PHONE pack.
        $text = 'Mans tālrunis 26123456, raksti uz janis.berzins@piemers.lv';

        $defaultRedacted = (new PiiRedactor())->redact($text)->text();
        self::assertStringContainsString('26123456', $defaultRedacted); // no consumer word: no boost
        self::assertStringNotContainsString('janis.berzins@piemers.lv', $defaultRedacted);

        $extended = ContextWords::default()->withWords(EntityType::PHONE, ['tālrunis', 'telefons']);
        $lvRedactor = new PiiRedactor(DetectorRegistry::withDefaults($extended));
        $lvRedacted = $lvRedactor->redact($text)->text();
        self::assertStringNotContainsString('26123456', $lvRedacted);
        self::assertStringNotContainsString('janis.berzins@piemers.lv', $lvRedacted);
    }
}
