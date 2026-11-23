<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\CreditMemo;

use Risecommerce\Mcp\Api\CreditMemoFieldResolverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Refunded line items.
 */
class ItemsResolver implements CreditMemoFieldResolverInterface
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
        return 30;
    }

    /** @inheritDoc */
    public function resolve(CreditmemoInterface $creditMemo, array $args): array
    {
        $rows = [];
        foreach ($creditMemo->getItems() as $item) {
            $rows[] = [
                'entity_id' => (int) $item->getEntityId(),
                'order_item_id' => (int) $item->getOrderItemId(),
                'sku' => (string) $item->getSku(),
                'name' => (string) $item->getName(),
                'qty' => (float) $item->getQty(),
                'price' => (float) $item->getPrice(),
                'row_total' => (float) $item->getRowTotal(),
                'tax_amount' => (float) $item->getTaxAmount(),
                'discount_amount' => (float) $item->getDiscountAmount(),
            ];
        }
        return $rows;
    }
}
