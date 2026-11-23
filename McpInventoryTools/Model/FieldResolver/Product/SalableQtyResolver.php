<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model\FieldResolver\Product;

use Risecommerce\Mcp\Api\ProductFieldResolverInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Throwable;

/**
 * Adds the `salable_qty` slice to `catalog.product.get` / `.list`.
 *
 * The catalog module's own `stock` slice reports legacy on-hand quantity. This
 * one reports what MSI will actually let a customer buy, plus the reservation
 * delta that explains any difference.
 */
class SalableQtyResolver implements ProductFieldResolverInterface
{
    public const KEY = 'salable_qty';

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        private readonly GetProductSalableQtyInterface $getProductSalableQty,
        private readonly GetReservationsQuantityInterface $getReservationsQuantity,
        private readonly DefaultStockProviderInterface $defaultStockProvider
    ) {
    }

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
        return 41;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ProductInterface $product, array $args): array
    {
        $sku = (string) $product->getSku();
        if ($sku === '') {
            return ['stock_id' => null, 'qty' => null, 'reserved' => null];
        }

        $stockId = isset($args['stock_id']) && is_numeric($args['stock_id'])
            ? (int) $args['stock_id']
            : $this->defaultStockProvider->getId();

        // Composite and non-stock product types have no salable quantity of
        // their own; MSI throws rather than returning zero.
        try {
            $salable = $this->getProductSalableQty->execute($sku, $stockId);
        } catch (Throwable) {
            return ['stock_id' => $stockId, 'qty' => null, 'reserved' => null];
        }

        $reserved = 0.0;
        try {
            $reserved = $this->getReservationsQuantity->execute($sku, $stockId);
        } catch (Throwable) {
            $reserved = 0.0;
        }

        return [
            'stock_id' => $stockId,
            'qty' => $salable,
            // Reservations are stored negative; report the amount held back.
            'reserved' => abs($reserved),
        ];
    }
}
