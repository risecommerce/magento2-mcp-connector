<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Invoice;

use Risecommerce\Mcp\Api\InvoiceFieldResolverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;

/**
 * Invoice identity slice — entity_id, increment_id, order_id, store_id, created_at.
 */
class IdentityResolver implements InvoiceFieldResolverInterface
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
    public function resolve(InvoiceInterface $invoice, array $args): array
    {
        return [
            'entity_id' => (int) $invoice->getEntityId(),
            'increment_id' => (string) $invoice->getIncrementId(),
            'order_id' => (int) $invoice->getOrderId(),
            'store_id' => (int) $invoice->getStoreId(),
            'created_at' => (string) $invoice->getCreatedAt(),
            'updated_at' => (string) $invoice->getUpdatedAt(),
        ];
    }
}
