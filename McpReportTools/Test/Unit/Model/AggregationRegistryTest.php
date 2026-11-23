<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpReportTools\Test\Unit\Model;

use Risecommerce\McpReportTools\Model\AggregationRegistry;
use Risecommerce\McpReportTools\Test\Unit\Model\Stub\BestsellersFactory;
use Risecommerce\McpReportTools\Test\Unit\Model\Stub\SalesFactory;
use Risecommerce\McpReportTools\Test\Unit\Model\Stub\TaxFactory;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class AggregationRegistryTest extends TestCase
{
    public function testGetCodesReturnsConfiguredKeys(): void
    {
        $registry = $this->registry();
        $this->assertSame(['sales', 'tax', 'bestsellers'], $registry->getCodes());
    }

    public function testGetFactoryReturnsRegisteredObject(): void
    {
        $factory = $this->registry()->getFactory('sales');
        $this->assertInstanceOf(SalesFactory::class, $factory);
    }

    public function testGetFactoryThrowsForUnknownCode(): void
    {
        $this->expectException(LocalizedException::class);
        $this->registry()->getFactory('unknown');
    }

    public function testGetResourceClassStripsFactorySuffix(): void
    {
        $this->assertSame(
            'Risecommerce\\McpReportTools\\Test\\Unit\\Model\\Stub\\Sales',
            $this->registry()->getResourceClass('sales')
        );
    }

    public function testGetResourceClassThrowsForUnknownCode(): void
    {
        $this->expectException(LocalizedException::class);
        $this->registry()->getResourceClass('unknown');
    }

    public function testGetFlagCodeReturnsValue(): void
    {
        $this->assertSame('report_order_aggregated', $this->registry()->getFlagCode('sales'));
    }

    public function testGetFlagCodeReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->registry()->getFlagCode('bestsellers'));
    }

    public function testResolveRequestedExpandsAll(): void
    {
        $this->assertSame(['sales', 'tax', 'bestsellers'], $this->registry()->resolveRequested('ALL'));
    }

    public function testResolveRequestedExpandsAllFromArray(): void
    {
        $this->assertSame(['sales', 'tax', 'bestsellers'], $this->registry()->resolveRequested(['ALL']));
    }

    public function testResolveRequestedEmptyInputExpandsToAll(): void
    {
        $this->assertSame(['sales', 'tax', 'bestsellers'], $this->registry()->resolveRequested(null));
        $this->assertSame(['sales', 'tax', 'bestsellers'], $this->registry()->resolveRequested([]));
    }

    public function testResolveRequestedPreservesOrderOfInput(): void
    {
        $this->assertSame(['tax', 'sales'], $this->registry()->resolveRequested(['tax', 'sales']));
    }

    public function testResolveRequestedDedupes(): void
    {
        $this->assertSame(['sales'], $this->registry()->resolveRequested(['sales', 'sales']));
    }

    public function testResolveRequestedRejectsUnknownCode(): void
    {
        $this->expectException(LocalizedException::class);
        $this->registry()->resolveRequested(['bogus']);
    }

    public function testResolveRequestedRejectsNonStringEntry(): void
    {
        $this->expectException(LocalizedException::class);
        $this->registry()->resolveRequested([123]);
    }

    private function registry(): AggregationRegistry
    {
        return new AggregationRegistry(
            [
                'sales' => new SalesFactory(),
                'tax' => new TaxFactory(),
                'bestsellers' => new BestsellersFactory(),
            ],
            [
                'sales' => 'report_order_aggregated',
                'tax' => 'report_tax_aggregated',
            ]
        );
    }
}
