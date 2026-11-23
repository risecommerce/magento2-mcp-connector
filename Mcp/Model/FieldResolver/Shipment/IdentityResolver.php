<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Shipment;

use Risecommerce\Mcp\Api\ShipmentFieldResolverInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Shipment identity slice.
 */
class IdentityResolver implements ShipmentFieldResolverInterface
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
    public function resolve(ShipmentInterface $shipment, array $args): array
    {
        return [
            'entity_id' => (int) $shipment->getEntityId(),
            'increment_id' => (string) $shipment->getIncrementId(),
            'order_id' => (int) $shipment->getOrderId(),
            'store_id' => (int) $shipment->getStoreId(),
            'total_qty' => (float) $shipment->getTotalQty(),
            'created_at' => (string) $shipment->getCreatedAt(),
            'updated_at' => (string) $shipment->getUpdatedAt(),
        ];
    }
}
