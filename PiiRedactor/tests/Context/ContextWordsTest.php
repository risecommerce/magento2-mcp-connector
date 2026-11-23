<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Context;

use Risecommerce\PiiRedactor\Context\ContextWords;
use Risecommerce\PiiRedactor\EntityType;
use PHPUnit\Framework\TestCase;

final class ContextWordsTest extends TestCase
{
    public function testDefaultPackShipsEnglishWordsOnly(): void
    {
        $words = ContextWords::default()->for(EntityType::PHONE);

        self::assertContains('phone', $words);
        self::assertNotContains('tālrunis', $words);
    }

    public function testNonePackIsEmpty(): void
    {
        self::assertSame([], ContextWords::none()->for(EntityType::PHONE));
    }

    public function testWithWordsMergesOntoExistingEntityWords(): void
    {
        $words = ContextWords::default()->withWords(EntityType::PHONE, ['tālrunis', 'telefons']);

        $phone = $words->for(EntityType::PHONE);
        self::assertContains('phone', $phone); // shipped word retained
        self::assertContains('tālrunis', $phone); // consumer word added
        self::assertContains('telefons', $phone);
    }

    public function testWithWordsSupportsCustomStringEntityTypes(): void
    {
        $words = ContextWords::none()->withWords('PASSPORT', ['passport', 'travel document']);

        self::assertSame(['passport', 'travel document'], $words->for('PASSPORT'));
    }

    public function testWithWordsDeduplicatesAndPreservesOrder(): void
    {
        $words = ContextWords::default()->withWords(EntityType::PHONE, ['phone', 'tālrunis', 'tālrunis']);

        $phone = $words->for(EntityType::PHONE);
        self::assertSame(array_values(array_unique($phone)), $phone);
        self::assertSame(1, count(array_keys($phone, 'phone', true)));
        self::assertSame(1, count(array_keys($phone, 'tālrunis', true)));
    }

    public function testDefaultsCoverNewEntityTypes(): void
    {
        $words = ContextWords::default();

        self::assertContains('token', $words->for(EntityType::API_KEY));
        self::assertContains('vat', $words->for(EntityType::VAT_ID));
        self::assertContains('latitude', $words->for(EntityType::GEO_COORDINATE));
        self::assertContains('swift', $words->for(EntityType::SWIFT_BIC));
        self::assertSame([], $words->for(EntityType::PRIVATE_KEY));
    }

    public function testWithWordsIsImmutable(): void
    {
        $base = ContextWords::default();
        $extended = $base->withWords(EntityType::PHONE, ['tālrunis']);

        self::assertNotContains('tālrunis', $base->for(EntityType::PHONE));
        self::assertContains('tālrunis', $extended->for(EntityType::PHONE));
    }
}
