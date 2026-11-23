<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model;

use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Throwable;

/**
 * Shared projection of an MSI stock, including the sources feeding it and the
 * websites it serves — the two things that decide what a stock actually does.
 */
class StockPayload
{
    /**
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannels
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly GetStockSourceLinksInterface $getStockSourceLinks,
        private readonly GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannels,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * @param StockInterface $stock
     * @return array<string, mixed>
     */
    public function toArray(StockInterface $stock): array
    {
        $stockId = (int) $stock->getStockId();

        return [
            'stock_id' => $stockId,
            'name' => (string) $stock->getName(),
            'sources' => $this->sources($stockId),
            'sales_channels' => $this->salesChannels($stockId),
        ];
    }

    /**
     * @param int $stockId
     * @return array<int, array<string, mixed>>
     */
    private function sources(int $stockId): array
    {
        $this->searchCriteriaBuilder->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId);

        $links = [];
        try {
            $result = $this->getStockSourceLinks->execute($this->searchCriteriaBuilder->create());
            foreach ($result->getItems() as $link) {
                $links[] = [
                    'source_code' => (string) $link->getSourceCode(),
                    'priority' => $link->getPriority() !== null ? (int) $link->getPriority() : null,
                ];
            }
        } catch (Throwable) {
            return [];
        }

        return $links;
    }

    /**
     * @param int $stockId
     * @return array<int, array<string, mixed>>
     */
    private function salesChannels(int $stockId): array
    {
        try {
            $channels = $this->getAssignedSalesChannels->execute($stockId);
        } catch (Throwable) {
            return [];
        }

        $rows = [];
        foreach ($channels as $channel) {
            $rows[] = [
                'type' => (string) $channel->getType(),
                'code' => (string) $channel->getCode(),
            ];
        }

        return $rows;
    }
}
