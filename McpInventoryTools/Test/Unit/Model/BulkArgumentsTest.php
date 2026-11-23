<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Test\Unit\Model;

use Risecommerce\McpInventoryTools\Model\BulkArguments;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class BulkArgumentsTest extends TestCase
{
    public function testDeduplicatesSkus(): void
    {
        $skus = $this->bulkArguments()->skus(['skus' => ['a', 'b', 'a']]);

        $this->assertSame(['a', 'b'], $skus);
    }

    public function testRejectsEmptyList(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('non-empty array');

        $this->bulkArguments()->skus(['skus' => []]);
    }

    public function testRejectsNonStringEntries(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('non-empty strings');

        $this->bulkArguments()->skus(['skus' => ['a', '']]);
    }

    /**
     * The cap is what keeps a single call from rewriting the whole catalog.
     */
    public function testRejectsListOverTheCap(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('at most');

        $skus = [];
        for ($i = 0; $i <= BulkArguments::MAX_SKUS; $i++) {
            $skus[] = 'sku-' . $i;
        }

        $this->bulkArguments()->skus(['skus' => $skus]);
    }

    public function testSourceCodesUseTheirOwnCap(): void
    {
        $codes = [];
        for ($i = 0; $i < BulkArguments::MAX_SOURCES; $i++) {
            $codes[] = 'source-' . $i;
        }

        $this->assertCount(
            BulkArguments::MAX_SOURCES,
            $this->bulkArguments()->sourceCodes(['source_codes' => $codes])
        );
    }

    public function testRequiredStringRejectsMissingValue(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('is required');

        $this->bulkArguments()->requiredString([], 'origin_source_code');
    }

    public function testRequiredStringReturnsValue(): void
    {
        $this->assertSame(
            'warehouse',
            $this->bulkArguments()->requiredString(['origin_source_code' => 'warehouse'], 'origin_source_code')
        );
    }

    /**
     * @return BulkArguments
     */
    private function bulkArguments(): BulkArguments
    {
        return new BulkArguments();
    }
}
