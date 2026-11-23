<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Test\Unit\Tool\Inventory\Stock;

use Risecommerce\McpInventoryTools\Tool\Inventory\Stock\StockDelete;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Covers the two refusals that keep this tool from taking a storefront's
 * availability down. Deliberately unit-only — exercising them against a live
 * store would risk the thing they protect.
 */
class StockDeleteTest extends TestCase
{
    private const DEFAULT_STOCK_ID = 1;

    public function testRefusesToDeleteTheDefaultStock(): void
    {
        $repository = $this->createMock(StockRepositoryInterface::class);
        $repository->expects($this->never())->method('deleteById');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('default stock cannot be deleted');

        $this->tool($repository)->execute(['stock_id' => self::DEFAULT_STOCK_ID]);
    }

    /**
     * Deleting a stock that still serves a website zeroes availability across
     * that whole site, so it is refused with the offending codes named.
     */
    public function testRefusesAStockThatStillServesWebsites(): void
    {
        $repository = $this->createMock(StockRepositoryInterface::class);
        $repository->method('get')->willReturn($this->stock(2, 'EU Stock'));
        $repository->expects($this->never())->method('deleteById');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('still serves website(s): base, eu_website');

        $this->tool($repository, ['base', 'eu_website'])->execute(['stock_id' => 2]);
    }

    public function testDeletesAStockWithNoSalesChannels(): void
    {
        $repository = $this->createMock(StockRepositoryInterface::class);
        $repository->method('get')->willReturn($this->stock(2, 'Spare Stock'));
        $repository->expects($this->once())->method('deleteById')->with(2);

        $result = $this->tool($repository)->execute(['stock_id' => 2]);

        $text = $result->getContent()[0]['text'] ?? null;
        $this->assertIsString($text);
        $this->assertStringContainsString('"deleted": true', $text);
    }

    public function testRequiresStockId(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('"stock_id" is required');

        $this->tool($this->createMock(StockRepositoryInterface::class))->execute([]);
    }

    /**
     * @param StockRepositoryInterface $repository
     * @param string[] $websiteCodes
     * @return StockDelete
     */
    private function tool(StockRepositoryInterface $repository, array $websiteCodes = []): StockDelete
    {
        $defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $defaultStockProvider->method('getId')->willReturn(self::DEFAULT_STOCK_ID);

        $channels = [];
        foreach ($websiteCodes as $code) {
            $channel = $this->createMock(SalesChannelInterface::class);
            $channel->method('getCode')->willReturn($code);
            $channels[] = $channel;
        }

        $getChannels = $this->createMock(GetAssignedSalesChannelsForStockInterface::class);
        $getChannels->method('execute')->willReturn($channels);

        return new StockDelete($repository, $defaultStockProvider, $getChannels);
    }

    /**
     * @param int $stockId
     * @param string $name
     * @return StockInterface
     */
    private function stock(int $stockId, string $name): StockInterface
    {
        $stock = $this->createMock(StockInterface::class);
        $stock->method('getStockId')->willReturn($stockId);
        $stock->method('getName')->willReturn($name);

        return $stock;
    }
}
