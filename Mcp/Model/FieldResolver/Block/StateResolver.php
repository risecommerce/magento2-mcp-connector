<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Block;

use Risecommerce\Mcp\Api\BlockFieldResolverInterface;
use Magento\Cms\Api\Data\BlockInterface;

/**
 * `state` slice: is_active.
 */
class StateResolver implements BlockFieldResolverInterface
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
    public function resolve(BlockInterface $block, array $args): array
    {
        unset($args);
        return [
            'is_active' => (bool) $block->isActive(),
        ];
    }
}
