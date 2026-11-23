<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Cms\Api\Data\BlockInterface;

/** Resolver for a named slice of `cms.block.list` / `cms.block.get` output. */
interface BlockFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param BlockInterface $block
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(BlockInterface $block, array $args): array;
}
