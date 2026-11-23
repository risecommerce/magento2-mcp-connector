<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;

interface OrderCommentFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param OrderStatusHistoryInterface $comment
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(OrderStatusHistoryInterface $comment, array $args): array;
}
