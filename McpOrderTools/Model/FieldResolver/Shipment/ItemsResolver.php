<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Model\FieldResolver\Shipment;

use Risecommerce\McpOrderTools\Api\ShipmentFieldResolverInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Lines shipped — qty + SKU + weight.
 */
class ItemsResolver implements ShipmentFieldResolverInterface
{
    public const KEY = 'items';

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 20;
    }

    /** @inheritDoc */
    public function resolve(ShipmentInterface $shipment, array $args): array
    {
        $rows = [];
        foreach ($shipment->getItems() as $item) {
            $rows[] = [
                'entity_id' => (int) $item->getEntityId(),
                'order_item_id' => (int) $item->getOrderItemId(),
                'sku' => (string) $item->getSku(),
                'name' => (string) $item->getName(),
                'qty' => (float) $item->getQty(),
                'price' => (float) $item->getPrice(),
                'row_total' => (float) $item->getRowTotal(),
                'weight' => (float) $item->getWeight(),
            ];
        }
        return $rows;
    }
}
