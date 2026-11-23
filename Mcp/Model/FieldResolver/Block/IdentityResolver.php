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
 * `identity` slice: block_id, identifier, title.
 */
class IdentityResolver implements BlockFieldResolverInterface
{
    public const KEY = 'identity';

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
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function resolve(BlockInterface $block, array $args): array
    {
        unset($args);
        return [
            'block_id' => (int) $block->getId(),
            'identifier' => (string) $block->getIdentifier(),
            'title' => (string) $block->getTitle(),
        ];
    }
}
