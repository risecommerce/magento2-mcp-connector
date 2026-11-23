<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\SalableQty;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockIdResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Throwable;

/**
 * MCP read tool `inventory.salable_qty.get`. What can actually be sold.
 *
 * Salable quantity is on-hand minus reservations (pending orders). Reporting
 * the reservation delta alongside it answers the usual follow-up question of
 * why salable is lower than the warehouse count.
 */
class SalableQtyGet implements ToolInterface
{
    public const TOOL_NAME = 'inventory.salable_qty.get';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_salable_qty_get';

    public const MAX_SKUS = 100;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsProductSalableInterface $isProductSalable
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param StockIdResolver $stockIdResolver
     */
    public function __construct(
        private readonly GetProductSalableQtyInterface $getProductSalableQty,
        private readonly IsProductSalableInterface $isProductSalable,
        private readonly GetReservationsQuantityInterface $getReservationsQuantity,
        private readonly StockIdResolver $stockIdResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Get Salable Quantity';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Report how many units of each SKU can actually be sold right now. '
            . 'Salable quantity is on-hand stock minus reservations held by orders '
            . 'that have not shipped yet, so it is usually lower than the source '
            . 'quantities from `inventory.source_item.list` — the `reserved` field '
            . 'shows the difference. Scope with `stock_id` or `website_code`; '
            . 'defaults to the default stock.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->array('sku', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->minItems(1)
                ->maxItems(self::MAX_SKUS)
                ->uniqueItems()
                ->description('SKUs to report on.')
                ->required())
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to evaluate against.'))
            ->string('website_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Website code; resolves to the stock serving it.'))
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    /**
     * @inheritDoc
     */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::READ;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationRequired(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $skus = $arguments['sku'] ?? null;
        if (!is_array($skus) || $skus === []) {
            throw new LocalizedException(__('"sku" must be a non-empty array.'));
        }

        $stockId = $this->stockIdResolver->resolve($arguments);

        $items = [];
        foreach ($skus as $sku) {
            if (!is_scalar($sku) || (string) $sku === '') {
                continue;
            }

            $items[] = $this->describe((string) $sku, $stockId);
        }

        $payload = [
            'stock_id' => $stockId,
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode salable quantity as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'stock_id' => $stockId,
                'sku_count' => count($items),
            ]
        );
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @return array<string, mixed>
     */
    private function describe(string $sku, int $stockId): array
    {
        $row = ['sku' => $sku];

        // Composite and non-stock types have no salable quantity; MSI throws
        // rather than returning zero, and "not applicable" is the honest answer.
        try {
            $row['qty'] = $this->getProductSalableQty->execute($sku, $stockId);
        } catch (LocalizedException $e) {
            $row['qty'] = null;
            $row['message'] = $e->getMessage();
        } catch (Throwable) {
            // An unknown SKU surfaces as a TypeError from deep inside MSI.
            // Report the cause rather than leaking the internal trace.
            $row['qty'] = null;
            $row['message'] = 'No salable quantity for this SKU — it may not exist, '
                . 'or its product type does not carry stock of its own.';
        }

        try {
            $row['is_salable'] = $this->isProductSalable->execute($sku, $stockId);
        } catch (Throwable) {
            $row['is_salable'] = null;
        }

        try {
            $row['reserved'] = abs($this->getReservationsQuantity->execute($sku, $stockId));
        } catch (Throwable) {
            $row['reserved'] = null;
        }

        return $row;
    }
}
