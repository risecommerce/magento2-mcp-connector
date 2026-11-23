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
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\BooleanBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\NumberBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockIdResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockItemConfigurationInterface;

/**
 * MCP write tool `inventory.stock_item_configuration.set`. PATCH-style.
 */
class StockItemConfigurationSet implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock_item_configuration.set';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_item_configuration_set';

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param SaveStockItemConfigurationInterface $saveStockItemConfiguration
     * @param StockIdResolver $stockIdResolver
     */
    public function __construct(
        private readonly GetStockItemConfigurationInterface $getStockItemConfiguration,
        private readonly SaveStockItemConfigurationInterface $saveStockItemConfiguration,
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
        return 'Set Stock Item Configuration';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Change stock settings for one SKU in one stock — backorders, '
            . 'minimum quantity, low-stock threshold, sale quantity limits. Only '
            . 'the fields you pass are changed, and setting any of them stops that '
            . 'field inheriting the global default. Pass the matching '
            . '`use_config_*` field as true to hand it back to the global setting.';
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
                ->description('Stock to write against.'))
            ->string('website_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Website code; resolves to the stock serving it.'))
            ->boolean('manage_stock', fn (BooleanBuilder $b) => $b
                ->description('Whether Magento tracks quantity for this product.'))
            ->boolean('use_config_manage_stock', fn (BooleanBuilder $b) => $b
                ->description('Inherit manage_stock from the global configuration.'))
            ->integer('backorders', fn (IntegerBuilder $i) => $i
                ->enum([0, 1, 2])
                ->description('0 = no backorders, 1 = allow, 2 = allow and notify.'))
            ->boolean('use_config_backorders', fn (BooleanBuilder $b) => $b
                ->description('Inherit backorders from the global configuration.'))
            ->number('min_qty', fn (NumberBuilder $n) => $n
                ->description('Quantity at which the product goes out of stock.'))
            ->boolean('use_config_min_qty', fn (BooleanBuilder $b) => $b
                ->description('Inherit min_qty from the global configuration.'))
            ->number('min_sale_qty', fn (NumberBuilder $n) => $n
                ->minimum(0)
                ->description('Minimum quantity allowed per order.'))
            ->boolean('use_config_min_sale_qty', fn (BooleanBuilder $b) => $b
                ->description('Inherit min_sale_qty from the global configuration.'))
            ->number('max_sale_qty', fn (NumberBuilder $n) => $n
                ->minimum(0)
                ->description('Maximum quantity allowed per order.'))
            ->boolean('use_config_max_sale_qty', fn (BooleanBuilder $b) => $b
                ->description('Inherit max_sale_qty from the global configuration.'))
            ->number('notify_stock_qty', fn (NumberBuilder $n) => $n
                ->description('Low-stock notification threshold.'))
            ->boolean('use_config_notify_stock_qty', fn (BooleanBuilder $b) => $b
                ->description('Inherit notify_stock_qty from the global configuration.'))
            ->number('qty_increments', fn (NumberBuilder $n) => $n
                ->minimum(0)
                ->description('Products must be bought in multiples of this.'))
            ->boolean('use_config_qty_increments', fn (BooleanBuilder $b) => $b
                ->description('Inherit qty_increments from the global configuration.'))
            ->boolean('enable_qty_increments', fn (BooleanBuilder $b) => $b
                ->description('Whether quantity increments apply.'))
            ->boolean('use_config_enable_qty_inc', fn (BooleanBuilder $b) => $b
                ->description('Inherit enable_qty_increments from the global configuration.'))
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
    public function getUnderlyingAclResource(): ?string
    {
        return 'Magento_Catalog::products';
    }

    /**
     * @inheritDoc
     */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::WRITE;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationRequired(): bool
    {
        return true;
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

        $this->applyFields($configuration, $arguments);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $configuration);

        $saved = $this->getStockItemConfiguration->execute($sku, $stockId);

        $payload = [
            'sku' => $sku,
            'stock_id' => $stockId,
            'manage_stock' => $saved->isManageStock(),
            'backorders' => $saved->getBackorders(),
            'min_qty' => $saved->getMinQty(),
            'min_sale_qty' => $saved->getMinSaleQty(),
            'max_sale_qty' => $saved->getMaxSaleQty(),
            'notify_stock_qty' => $saved->getNotifyStockQty(),
            'qty_increments' => $saved->getQtyIncrements(),
            'enable_qty_increments' => $saved->isEnableQtyIncrements(),
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

    /**
     * Setting a value implies overriding the global default, so the matching
     * `use_config_*` flag is cleared unless the caller asked for inheritance.
     *
     * @param StockItemConfigurationInterface $configuration
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @return void
     */
    private function applyFields(StockItemConfigurationInterface $configuration, array $arguments): void
    {
        if (array_key_exists('manage_stock', $arguments)) {
            $configuration->setUseConfigManageStock(false);
            $configuration->setManageStock((bool) $arguments['manage_stock']);
        }
        if (isset($arguments['backorders']) && is_numeric($arguments['backorders'])) {
            $configuration->setUseConfigBackorders(false);
            $configuration->setBackorders((int) $arguments['backorders']);
        }
        if (isset($arguments['min_qty']) && is_numeric($arguments['min_qty'])) {
            $configuration->setUseConfigMinQty(false);
            $configuration->setMinQty((float) $arguments['min_qty']);
        }
        if (isset($arguments['min_sale_qty']) && is_numeric($arguments['min_sale_qty'])) {
            $configuration->setUseConfigMinSaleQty(false);
            $configuration->setMinSaleQty((float) $arguments['min_sale_qty']);
        }
        if (isset($arguments['max_sale_qty']) && is_numeric($arguments['max_sale_qty'])) {
            $configuration->setUseConfigMaxSaleQty(false);
            $configuration->setMaxSaleQty((float) $arguments['max_sale_qty']);
        }
        if (isset($arguments['notify_stock_qty']) && is_numeric($arguments['notify_stock_qty'])) {
            $configuration->setUseConfigNotifyStockQty(false);
            $configuration->setNotifyStockQty((float) $arguments['notify_stock_qty']);
        }
        if (isset($arguments['qty_increments']) && is_numeric($arguments['qty_increments'])) {
            $configuration->setUseConfigQtyIncrements(false);
            $configuration->setQtyIncrements((float) $arguments['qty_increments']);
        }
        if (array_key_exists('enable_qty_increments', $arguments)) {
            $configuration->setUseConfigEnableQtyInc(false);
            $configuration->setEnableQtyIncrements((bool) $arguments['enable_qty_increments']);
        }

        // Explicit inheritance requests win, so they run last.
        $inheritance = [
            'use_config_manage_stock' => 'setUseConfigManageStock',
            'use_config_backorders' => 'setUseConfigBackorders',
            'use_config_min_qty' => 'setUseConfigMinQty',
            'use_config_min_sale_qty' => 'setUseConfigMinSaleQty',
            'use_config_max_sale_qty' => 'setUseConfigMaxSaleQty',
            'use_config_notify_stock_qty' => 'setUseConfigNotifyStockQty',
            'use_config_qty_increments' => 'setUseConfigQtyIncrements',
            'use_config_enable_qty_inc' => 'setUseConfigEnableQtyInc',
        ];

        foreach ($inheritance as $key => $setter) {
            if (array_key_exists($key, $arguments)) {
                $configuration->{$setter}((bool) $arguments[$key]);
            }
        }
    }
}
