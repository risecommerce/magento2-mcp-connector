<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\Bulk;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\NumberBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ObjectBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\BulkArguments;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\BulkPartialInventoryTransferInterface;
use Magento\InventoryCatalogApi\Api\Data\PartialInventoryTransferItemInterface;
use Magento\InventoryCatalogApi\Api\Data\PartialInventoryTransferItemInterfaceFactory;

/**
 * MCP write tool `inventory.bulk.partial_transfer`. Moves a specified quantity
 * per SKU between two sources, rather than the whole balance.
 */
class BulkPartialTransfer implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.bulk.partial_transfer';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_bulk_partial_transfer';

    /**
     * @param BulkPartialInventoryTransferInterface $bulkPartialTransfer
     * @param PartialInventoryTransferItemInterfaceFactory $transferItemFactory
     * @param BulkArguments $bulkArguments
     */
    public function __construct(
        private readonly BulkPartialInventoryTransferInterface $bulkPartialTransfer,
        private readonly PartialInventoryTransferItemInterfaceFactory $transferItemFactory,
        private readonly BulkArguments $bulkArguments
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
        return 'Bulk Partial Transfer Inventory';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Move a specific quantity of each SKU from one source to another — '
            . 'a stock rebalance rather than a full move. The origin keeps '
            . 'whatever is left over and stays assigned. Use '
            . '`inventory.bulk.transfer` to move entire balances instead.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('origin_source_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Source to move stock out of.')
                ->required())
            ->string('destination_source_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Source to move stock into.')
                ->required())
            ->array('items', fn (ArrayBuilder $a) => $a
                ->ofObjects(fn (ObjectBuilder $o) => $o
                    ->string('sku', fn (StringBuilder $s) => $s
                        ->minLength(1)
                        ->description('Product SKU.')
                        ->required())
                    ->number('qty', fn (NumberBuilder $n) => $n
                        ->exclusiveMinimum(0)
                        ->description('Quantity to move.')
                        ->required()))
                ->minItems(1)
                ->maxItems(BulkArguments::MAX_SKUS)
                ->description('Per-SKU quantities to move.')
                ->required())
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
        return 'Magento_InventoryApi::stock_source_item_assign';
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
        $origin = $this->bulkArguments->requiredString($arguments, 'origin_source_code');
        $destination = $this->bulkArguments->requiredString($arguments, 'destination_source_code');

        if ($origin === $destination) {
            throw new LocalizedException(__('Origin and destination sources must differ.'));
        }

        $items = $arguments['items'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new LocalizedException(__('"items" must be a non-empty array.'));
        }
        if (count($items) > BulkArguments::MAX_SKUS) {
            throw new LocalizedException(__(
                '"items" accepts at most %1 entries; %2 given.',
                BulkArguments::MAX_SKUS,
                count($items)
            ));
        }

        $transferItems = [];
        $moved = [];
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['sku']) || !is_scalar($item['sku'])) {
                throw new LocalizedException(__('Each entry in "items" requires a "sku".'));
            }
            if (!isset($item['qty']) || !is_numeric($item['qty']) || (float) $item['qty'] <= 0) {
                throw new LocalizedException(__('Each entry in "items" requires a positive "qty".'));
            }

            $sku = (string) $item['sku'];
            $qty = (float) $item['qty'];

            /** @var PartialInventoryTransferItemInterface $transferItem */
            $transferItem = $this->transferItemFactory->create();
            $transferItem->setSku($sku);
            $transferItem->setQty($qty);

            $transferItems[] = $transferItem;
            $moved[] = ['sku' => $sku, 'qty' => $qty];
        }

        $this->bulkPartialTransfer->execute($origin, $destination, $transferItems);

        $payload = [
            'origin_source_code' => $origin,
            'destination_source_code' => $destination,
            'item_count' => count($transferItems),
            'items' => $moved,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode partial transfer result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'origin_source_code' => $origin,
                'destination_source_code' => $destination,
                'item_count' => count($transferItems),
            ]
        );
    }
}
