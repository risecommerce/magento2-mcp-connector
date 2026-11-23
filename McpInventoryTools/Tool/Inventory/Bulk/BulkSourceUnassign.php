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
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\BulkArguments;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;

/**
 * MCP write tool `inventory.bulk.source_unassign`.
 *
 * Destructive: the source items are deleted outright, taking their quantities
 * with them. There is no undo and no per-item report.
 */
class BulkSourceUnassign implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.bulk.source_unassign';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_bulk_source_unassign';

    /**
     * @param BulkSourceUnassignInterface $bulkSourceUnassign
     * @param BulkArguments $bulkArguments
     */
    public function __construct(
        private readonly BulkSourceUnassignInterface $bulkSourceUnassign,
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
        return 'Bulk Unassign Sources';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Remove every listed SKU from every listed source in one operation. '
            . 'DESTRUCTIVE: the source items are deleted along with their '
            . 'quantities, salable quantity drops on every website the affected '
            . 'stocks serve, and there is no undo. To move stock rather than '
            . 'destroy it, use `inventory.bulk.transfer`.';
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
                ->description('SKUs to unassign.')
                ->required())
            ->array('source_codes', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->minItems(1)
                ->maxItems(BulkArguments::MAX_SOURCES)
                ->uniqueItems()
                ->description('Sources to unassign them from.')
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
        $skus = $this->bulkArguments->skus($arguments);
        $sourceCodes = $this->bulkArguments->sourceCodes($arguments);

        $affected = $this->bulkSourceUnassign->execute($skus, $sourceCodes);

        $payload = [
            'affected_source_items' => $affected,
            'sku_count' => count($skus),
            'source_codes' => $sourceCodes,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode bulk unassign result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'affected_source_items' => $affected,
                'sku_count' => count($skus),
                'source_codes' => $sourceCodes,
            ]
        );
    }
}
