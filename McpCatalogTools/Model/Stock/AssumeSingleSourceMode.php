<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCatalogTools\Model\Stock;

use Risecommerce\McpCatalogTools\Api\SingleSourceModeCheckerInterface;

/**
 * Default checker: assumes single-source. Overridden by
 * `Risecommerce_McpInventoryTools` when MSI is in play.
 */
class AssumeSingleSourceMode implements SingleSourceModeCheckerInterface
{
    /**
     * @inheritDoc
     */
    public function isSingleSourceMode(): bool
    {
        return true;
    }
}
