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
use Magento\InventoryCatalogApi\Api\BulkSourceAssignInterface;

/**
 * MCP write tool `inventory.bulk.source_assign`. Assigns many SKUs to many
 * sources in one call.
 */
class BulkSourceAssign implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.bulk.source_assign';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_bulk_source_assign';

    /**
     * @param BulkSourceAssignInterface $bulkSourceAssign
     * @param BulkArguments $bulkArguments
     */
    public function __construct(
        private readonly BulkSourceAssignInterface $bulkSourceAssign,
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
        return 'Bulk Assign Sources';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Assign every listed SKU to every listed source in one operation, '
            . 'creating source items with zero quantity where they do not exist. '
            . 'Use this to stand up a new warehouse; set quantities afterwards with '
            . '`inventory.source_item.set`. Affects up to '
            . BulkArguments::MAX_SKUS . ' SKUs per call.';
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
                ->description('SKUs to assign.')
                ->required())
            ->array('source_codes', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->minItems(1)
                ->maxItems(BulkArguments::MAX_SOURCES)
                ->uniqueItems()
                ->description('Sources to assign them to.')
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

        $affected = $this->bulkSourceAssign->execute($skus, $sourceCodes);

        $payload = [
            'affected_source_items' => $affected,
            'sku_count' => count($skus),
            'source_codes' => $sourceCodes,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode bulk assign result as JSON.'));
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
