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
 * Timestamp slice — created_at, updated_at.
 */
class TimestampsResolver implements ProductFieldResolverInterface
{
    public const KEY = 'timestamps';

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
        return 100;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ProductInterface $product, array $args): array
    {
        unset($args);
        return [
            'created_at' => $product->getCreatedAt() !== null ? (string) $product->getCreatedAt() : null,
            'updated_at' => $product->getUpdatedAt() !== null ? (string) $product->getUpdatedAt() : null,
        ];
    }
}
