<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model\Stock;

use Risecommerce\McpCatalogTools\Api\SingleSourceModeCheckerInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * MSI-backed replacement for the catalog module's optimistic default. Lets
 * `catalog.product.stock.set` warn when a legacy write would silently resolve
 * to the default source.
 */
class MsiSingleSourceModeChecker implements SingleSourceModeCheckerInterface
{
    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        private readonly IsSingleSourceModeInterface $isSingleSourceMode
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isSingleSourceMode(): bool
    {
        return $this->isSingleSourceMode->execute();
    }
}
