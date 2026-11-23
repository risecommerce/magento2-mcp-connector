<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCatalogTools\Model\FieldResolver\Product;

use Risecommerce\McpCatalogTools\Api\ProductFieldResolverInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * State slice — status, visibility. Kept as raw ints (Magento catalog
 * constants), not remapped to label strings: this is a data API, not a UI.
 */
class StateResolver implements ProductFieldResolverInterface
{
    public const KEY = 'state';

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return 20;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ProductInterface $product, array $args): array
    {
        unset($args);
        return [
            'status' => $product->getStatus() !== null ? (int) $product->getStatus() : null,
            'visibility' => $product->getVisibility() !== null ? (int) $product->getVisibility() : null,
        ];
    }
}
