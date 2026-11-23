<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Order;

use Risecommerce\Mcp\Api\OrderFieldResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * created_at / updated_at as stored in the database (typically UTC).
 */
class TimestampsResolver implements OrderFieldResolverInterface
{
    public const KEY = 'timestamps';

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 80;
    }

    /** @inheritDoc */
    public function resolve(OrderInterface $order, array $args): array
    {
        return [
            'created_at' => (string) $order->getCreatedAt(),
            'updated_at' => (string) $order->getUpdatedAt(),
        ];
    }
}
