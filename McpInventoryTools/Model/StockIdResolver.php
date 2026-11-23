<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Resolves which MSI stock a tool call is about: an explicit `stock_id`, the
 * stock serving a `website_code`, or the default stock.
 */
class StockIdResolver
{
    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly StockResolverInterface $stockResolver
    ) {
    }

    /**
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @return int
     * @throws LocalizedException
     */
    public function resolve(array $arguments): int
    {
        if (isset($arguments['stock_id']) && is_numeric($arguments['stock_id'])) {
            return (int) $arguments['stock_id'];
        }

        if (isset($arguments['website_code']) && is_string($arguments['website_code'])
            && $arguments['website_code'] !== ''
        ) {
            try {
                $stock = $this->stockResolver->execute(
                    SalesChannelInterface::TYPE_WEBSITE,
                    $arguments['website_code']
                );
            } catch (Exception $e) {
                throw new LocalizedException(
                    __('No stock is assigned to website "%1".', $arguments['website_code']),
                    $e
                );
            }

            return (int) $stock->getStockId();
        }

        return $this->defaultStockProvider->getId();
    }
}
