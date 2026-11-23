<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\Configuration;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockIdResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;

/**
 * MCP read tool `inventory.stock_item_configuration.get`. Per (sku, stock)
 * thresholds — MSI's replacement for the legacy per-product stock settings.
 */
class StockItemConfigurationGet implements ToolInterface
{
    public const TOOL_NAME = 'inventory.stock_item_configuration.get';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_item_configuration_get';

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param StockIdResolver $stockIdResolver
     */
    public function __construct(
        private readonly GetStockItemConfigurationInterface $getStockItemConfiguration,
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
        return 'Get Stock Item Configuration';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Read the stock settings for one SKU in one stock — backorders, '
            . 'minimum quantity, notification threshold, sale quantity limits, and '
            . 'whether stock is managed at all. Each `use_config_*` flag says '
            . 'whether the value is inherited from the global configuration or set '
            . 'on the product.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('sku', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Product SKU.')
                ->required())
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to read against.'))
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
        $sku = isset($arguments['sku']) && is_scalar($arguments['sku']) ? (string) $arguments['sku'] : '';
        if ($sku === '') {
            throw new LocalizedException(__('"sku" is required.'));
        }

        $stockId = $this->stockIdResolver->resolve($arguments);
        $configuration = $this->getStockItemConfiguration->execute($sku, $stockId);

        $payload = [
            'sku' => $sku,
            'stock_id' => $stockId,
            'is_qty_decimal' => $configuration->isQtyDecimal(),
            'manage_stock' => $configuration->isManageStock(),
            'use_config_manage_stock' => $configuration->isUseConfigManageStock(),
            'backorders' => $configuration->getBackorders(),
            'use_config_backorders' => $configuration->isUseConfigBackorders(),
            'min_qty' => $configuration->getMinQty(),
            'use_config_min_qty' => $configuration->isUseConfigMinQty(),
            'min_sale_qty' => $configuration->getMinSaleQty(),
            'use_config_min_sale_qty' => $configuration->isUseConfigMinSaleQty(),
            'max_sale_qty' => $configuration->getMaxSaleQty(),
            'use_config_max_sale_qty' => $configuration->isUseConfigMaxSaleQty(),
            'notify_stock_qty' => $configuration->getNotifyStockQty(),
            'use_config_notify_stock_qty' => $configuration->isUseConfigNotifyStockQty(),
            'qty_increments' => $configuration->getQtyIncrements(),
            'use_config_qty_increments' => $configuration->isUseConfigQtyIncrements(),
            'enable_qty_increments' => $configuration->isEnableQtyIncrements(),
            'use_config_enable_qty_inc' => $configuration->isUseConfigEnableQtyInc(),
            'stock_threshold_qty' => $configuration->getStockThresholdQty(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode stock item configuration as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['sku' => $sku, 'stock_id' => $stockId]
        );
    }
}
