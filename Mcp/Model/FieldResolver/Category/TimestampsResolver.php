<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Category;

use Risecommerce\Mcp\Api\CategoryFieldResolverInterface;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Timestamp slice — created_at, updated_at.
 */
class TimestampsResolver implements CategoryFieldResolverInterface
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
    public function resolve(CategoryInterface $category, array $args): array
    {
        unset($args);
        return [
            'created_at' => $category->getCreatedAt() !== null ? (string) $category->getCreatedAt() : null,
            'updated_at' => $category->getUpdatedAt() !== null ? (string) $category->getUpdatedAt() : null,
        ];
    }
}
