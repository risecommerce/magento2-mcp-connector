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
use Magento\Catalog\Model\Category as CategoryModel;

/**
 * Product-assignment slice — product ids directly assigned to the category.
 * Wired into `catalog.category.get` but not `.list`: broad list queries
 * returning tens of thousands of SKUs per row blow the response size budget.
 */
class ProductsResolver implements CategoryFieldResolverInterface
{
    public const KEY = 'products';

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
        return 80;
    }

    /**
     * @inheritDoc
     */
    public function resolve(CategoryInterface $category, array $args): array
    {
        unset($args);
        if (!$category instanceof CategoryModel) {
            return ['product_ids' => []];
        }

        $ids = $category->getProductCollection()->getAllIds();

        $out = [];
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $out[] = (int) $id;
            }
        }

        return ['product_ids' => array_values(array_unique($out))];
    }
}
