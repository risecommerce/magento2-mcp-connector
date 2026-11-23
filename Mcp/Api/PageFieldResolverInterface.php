<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Cms\Api\Data\PageInterface;

/** Resolver for a named slice of `cms.page.list` / `cms.page.get` output. */
interface PageFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param PageInterface $page
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(PageInterface $page, array $args): array;
}
