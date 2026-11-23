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
 * State slice — is_active, include_in_menu.
 */
class StateResolver implements CategoryFieldResolverInterface
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
    public function resolve(CategoryInterface $category, array $args): array
    {
        unset($args);
        $isActive = $category->getIsActive();
        $includeInMenu = $category->getIncludeInMenu();

        return [
            'is_active' => $isActive !== null ? (bool) $isActive : null,
            'include_in_menu' => $includeInMenu !== null ? (bool) $includeInMenu : null,
        ];
    }
}
