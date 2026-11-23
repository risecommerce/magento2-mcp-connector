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
use Risecommerce\Mcp\Model\Tool\Schema\Builder\BooleanBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\BulkArguments;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\BulkInventoryTransferInterface;

/**
 * MCP write tool `inventory.bulk.transfer`. Moves whole quantities between two
 * sources — the "closing a warehouse" operation.
 */
class BulkTransfer implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.bulk.transfer';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_bulk_transfer';

    /**
     * @param BulkInventoryTransferInterface $bulkInventoryTransfer
     * @param BulkArguments $bulkArguments
     */
    public function __construct(
        private readonly BulkInventoryTransferInterface $bulkInventoryTransfer,
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
        return 'Bulk Transfer Inventory';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Move the full quantity of every listed SKU from one source to '
            . 'another. Quantities are added to the destination and zeroed at the '
            . 'origin. With `unassign_from_origin: true` the origin source items '
            . 'are deleted as well, which is what you want when closing a '
            . 'warehouse. DESTRUCTIVE and not reversible — to move partial '
            . 'quantities use `inventory.bulk.partial_transfer`.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->array('skus', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->minItems(1)
                ->maxItems(BulkArguments::MAX_SKUS)
                ->uniqueItems()
                ->description('SKUs to move.')
                ->required())
            ->string('origin_source_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Source to move stock out of.')
                ->required())
            ->string('destination_source_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Source to move stock into.')
                ->required())
            ->boolean('unassign_from_origin', fn (BooleanBuilder $b) => $b
                ->description('Also delete the origin source items. Defaults to false.'))
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
        $skus = $this->bulkArguments->skus($arguments);
        $origin = $this->bulkArguments->requiredString($arguments, 'origin_source_code');
        $destination = $this->bulkArguments->requiredString($arguments, 'destination_source_code');

        if ($origin === $destination) {
            throw new LocalizedException(__('Origin and destination sources must differ.'));
        }

        $unassign = isset($arguments['unassign_from_origin']) && (bool) $arguments['unassign_from_origin'];

        $transferred = $this->bulkInventoryTransfer->execute($skus, $origin, $destination, $unassign);

        $payload = [
            'transferred' => $transferred,
            'sku_count' => count($skus),
            'origin_source_code' => $origin,
            'destination_source_code' => $destination,
            'unassigned_from_origin' => $unassign,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode transfer result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'sku_count' => count($skus),
                'origin_source_code' => $origin,
                'destination_source_code' => $destination,
                'unassigned_from_origin' => $unassign,
            ]
        );
    }
}
