<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\SourceItem;

use Exception;
use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\NumberBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ObjectBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\ServiceErrorMessage;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;

/**
 * MCP write tool `inventory.source_item.set`. The core inventory write: sets
 * quantity and status per (sku, source).
 *
 * Saves in one batch — {@see SourceItemsSaveInterface} is natively a bulk API,
 * and going item-by-item would reindex per row.
 */
class SourceItemSet implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.source_item.set';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_source_item_set';

    public const MAX_ITEMS = 500;

    /**
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowed
     * @param ServiceErrorMessage $serviceErrorMessage
     */
    public function __construct(
        private readonly SourceItemsSaveInterface $sourceItemsSave,
        private readonly SourceItemInterfaceFactory $sourceItemFactory,
        private readonly IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowed,
        private readonly ServiceErrorMessage $serviceErrorMessage
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
        return 'Set Source Item Quantities';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Set on-hand quantity and stock status for one or more (SKU, '
            . 'source) pairs — the main way to manage inventory on a multi-source '
            . 'store. Discover valid `source_code` values with '
            . '`inventory.source.list`. Processes up to ' . self::MAX_ITEMS
            . ' items per call. Composite products (configurable, bundle, grouped) '
            . 'have no source items of their own and are reported as skipped; set '
            . 'quantities on their child SKUs instead.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->array('items', fn (ArrayBuilder $a) => $a
                ->ofObjects(fn (ObjectBuilder $o) => $o
                    ->string('sku', fn (StringBuilder $s) => $s
                        ->minLength(1)
                        ->description('Product SKU.')
                        ->required())
                    ->string('source_code', fn (StringBuilder $s) => $s
                        ->minLength(1)
                        ->description('Source to write to, e.g. default.')
                        ->required())
                    ->number('quantity', fn (NumberBuilder $n) => $n
                        ->description('On-hand quantity at this source.')
                        ->required())
                    ->integer('status', fn (IntegerBuilder $i) => $i
                        ->enum([0, 1])
                        ->description('1 = in stock, 0 = out of stock. Defaults to 1 when quantity is above zero.')))
                ->minItems(1)
                ->maxItems(self::MAX_ITEMS)
                ->description('Source-item quantities to write.')
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
        $items = $arguments['items'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new LocalizedException(__('"items" must be a non-empty array.'));
        }

        $sourceItems = [];
        $results = [];
        $skipped = 0;

        foreach ($items as $index => $item) {
            $validated = $this->validateItem((int) $index, $item);
            $results[] = $validated['result'];

            if (!isset($validated['source_item'])) {
                $skipped++;
                continue;
            }

            $sourceItems[] = $validated['source_item'];
        }

        $saved = count($sourceItems);
        if ($sourceItems !== []) {
            try {
                $this->sourceItemsSave->execute($sourceItems);
            } catch (Exception $e) {
                throw new LocalizedException(
                    __('Failed to save source items: %1', $this->serviceErrorMessage->from($e)),
                    $e
                );
            }
        }

        $payload = [
            'saved' => $saved,
            'skipped' => $skipped,
            'results' => $results,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode source item result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'saved' => $saved,
                'skipped' => $skipped,
                'item_count' => count($items),
            ]
        );
    }

    /**
     * @param int $index
     * @param mixed $item
     * @return array{result: array<string, mixed>, source_item?: SourceItemInterface}
     */
    private function validateItem(int $index, mixed $item): array
    {
        if (!is_array($item)) {
            return [
                'result' => [
                    'index' => $index,
                    'sku' => null,
                    'status' => 'skipped',
                    'message' => 'Item must be an object.',
                ],
            ];
        }

        $sku = isset($item['sku']) && is_scalar($item['sku']) ? (string) $item['sku'] : '';
        $sourceCode = isset($item['source_code']) && is_scalar($item['source_code'])
            ? (string) $item['source_code']
            : '';

        if ($sku === '' || $sourceCode === '') {
            return [
                'result' => [
                    'index' => $index,
                    'sku' => $sku !== '' ? $sku : null,
                    'status' => 'skipped',
                    'message' => 'Item requires both "sku" and "source_code".',
                ],
            ];
        }

        if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
            return [
                'result' => [
                    'index' => $index,
                    'sku' => $sku,
                    'source_code' => $sourceCode,
                    'status' => 'skipped',
                    'message' => 'Item requires a numeric "quantity".',
                ],
            ];
        }

        if (!$this->isSourceItemManagementAllowed->execute($sku)) {
            return [
                'result' => [
                    'index' => $index,
                    'sku' => $sku,
                    'source_code' => $sourceCode,
                    'status' => 'skipped',
                    'message' => 'This product type does not carry its own source items. '
                        . 'Set quantities on its child SKUs instead.',
                ],
            ];
        }

        $quantity = (float) $item['quantity'];
        $status = isset($item['status']) && is_numeric($item['status'])
            ? (int) $item['status']
            : (int) ($quantity > 0);

        /** @var SourceItemInterface $sourceItem */
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSku($sku);
        $sourceItem->setSourceCode($sourceCode);
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus($status);

        return [
            'source_item' => $sourceItem,
            'result' => [
                'index' => $index,
                'sku' => $sku,
                'source_code' => $sourceCode,
                'status' => 'saved',
                'quantity' => $quantity,
                'stock_status' => $status,
            ],
        ];
    }
}
