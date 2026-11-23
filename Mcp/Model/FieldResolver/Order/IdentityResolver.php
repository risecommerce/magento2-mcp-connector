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
 * Row identity slice — entity_id, increment_id, store_id.
 */
class IdentityResolver implements OrderFieldResolverInterface
{
    public const KEY = 'identity';

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 10;
    }

    /** @inheritDoc */
    public function resolve(OrderInterface $order, array $args): array
    {
        return [
            'entity_id' => (int) $order->getEntityId(),
            'increment_id' => (string) $order->getIncrementId(),
            'store_id' => (int) $order->getStoreId(),
        ];
    }
}
