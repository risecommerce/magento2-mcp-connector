<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Page;

use Risecommerce\Mcp\Api\PageFieldResolverInterface;
use Magento\Cms\Api\Data\PageInterface;

/**
 * `state` slice: is_active, sort_order.
 */
class StateResolver implements PageFieldResolverInterface
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
    public function resolve(PageInterface $page, array $args): array
    {
        unset($args);
        return [
            'is_active' => (bool) $page->isActive(),
            'sort_order' => $page->getSortOrder() !== null ? (int) $page->getSortOrder() : null,
        ];
    }
}
