<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Test\Unit\Model;

use Risecommerce\McpInventoryTools\Model\StockIdResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use PHPUnit\Framework\TestCase;

class StockIdResolverTest extends TestCase
{
    private const DEFAULT_STOCK_ID = 1;

    public function testExplicitStockIdWins(): void
    {
        $resolver = $this->resolver();

        $this->assertSame(7, $resolver->resolve(['stock_id' => 7, 'website_code' => 'base']));
    }

    public function testFallsBackToDefaultStock(): void
    {
        $this->assertSame(self::DEFAULT_STOCK_ID, $this->resolver()->resolve([]));
    }

    public function testResolvesStockFromWebsiteCode(): void
    {
        $stock = $this->createMock(StockInterface::class);
        $stock->method('getStockId')->willReturn(4);

        $stockResolver = $this->createMock(StockResolverInterface::class);
        $stockResolver->expects($this->once())
            ->method('execute')
            ->with('website', 'eu')
            ->willReturn($stock);

        $this->assertSame(4, $this->resolver($stockResolver)->resolve(['website_code' => 'eu']));
    }

    /**
     * An unassigned website surfaces as a readable message, not the raw MSI
     * exception.
     */
    public function testUnknownWebsiteRaisesReadableError(): void
    {
        $stockResolver = $this->createMock(StockResolverInterface::class);
        $stockResolver->method('execute')->willThrowException(new NoSuchEntityException(__('nope')));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('No stock is assigned to website "ghost"');

        $this->resolver($stockResolver)->resolve(['website_code' => 'ghost']);
    }

    /**
     * @param StockResolverInterface|null $stockResolver
     * @return StockIdResolver
     */
    private function resolver(?StockResolverInterface $stockResolver = null): StockIdResolver
    {
        $defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $defaultStockProvider->method('getId')->willReturn(self::DEFAULT_STOCK_ID);

        return new StockIdResolver(
            $defaultStockProvider,
            $stockResolver ?? $this->createMock(StockResolverInterface::class)
        );
    }
}
