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
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ObjectBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Risecommerce\McpInventoryTools\Model\ServiceErrorMessage;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;

/**
 * MCP write tool `inventory.source_item.delete`. Unassigns SKUs from sources.
 *
 * Deleting the row is not the same as zeroing quantity: the product stops being
 * stocked at that source entirely.
 */
class SourceItemDelete implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.source_item.delete';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_source_item_delete';

    public const MAX_ITEMS = 500;

    /**
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ServiceErrorMessage $serviceErrorMessage
     */
    public function __construct(
        private readonly SourceItemsDeleteInterface $sourceItemsDelete,
        private readonly SourceItemRepositoryInterface $sourceItemRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
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
        return 'Delete Source Items';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Unassign one or more (SKU, source) pairs, so the product is no '
            . 'longer stocked at that source. This is different from setting '
            . 'quantity to zero — the row is removed and the source stops '
            . 'contributing to salable quantity. Pairs that do not exist are '
            . 'reported as skipped rather than failing the batch.';
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
                        ->description('Source to unassign from.')
                        ->required()))
                ->minItems(1)
                ->maxItems(self::MAX_ITEMS)
                ->description('(SKU, source) pairs to unassign.')
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

        $requested = [];
        $results = [];
        $skipped = 0;

        foreach ($items as $index => $item) {
            $sku = is_array($item) && isset($item['sku']) && is_scalar($item['sku'])
                ? (string) $item['sku']
                : '';
            $sourceCode = is_array($item) && isset($item['source_code']) && is_scalar($item['source_code'])
                ? (string) $item['source_code']
                : '';

            if ($sku === '' || $sourceCode === '') {
                $results[] = [
                    'index' => (int) $index,
                    'sku' => $sku !== '' ? $sku : null,
                    'status' => 'skipped',
                    'message' => 'Item requires both "sku" and "source_code".',
                ];
                $skipped++;
                continue;
            }

            $requested[$sku . "\0" . $sourceCode] = ['index' => (int) $index, 'sku' => $sku, 'source' => $sourceCode];
        }

        $existing = $this->loadExisting($requested);

        $toDelete = [];
        foreach ($requested as $key => $meta) {
            if (!isset($existing[$key])) {
                $results[] = [
                    'index' => $meta['index'],
                    'sku' => $meta['sku'],
                    'source_code' => $meta['source'],
                    'status' => 'skipped',
                    'message' => 'No source item exists for this pair.',
                ];
                $skipped++;
                continue;
            }

            $toDelete[] = $existing[$key];
            $results[] = [
                'index' => $meta['index'],
                'sku' => $meta['sku'],
                'source_code' => $meta['source'],
                'status' => 'deleted',
            ];
        }

        if ($toDelete !== []) {
            try {
                $this->sourceItemsDelete->execute($toDelete);
            } catch (Exception $e) {
                throw new LocalizedException(
                    __('Failed to delete source items: %1', $this->serviceErrorMessage->from($e)),
                    $e
                );
            }
        }

        $payload = [
            'deleted' => count($toDelete),
            'skipped' => $skipped,
            'results' => $results,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode delete result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'deleted' => count($toDelete),
                'skipped' => $skipped,
            ]
        );
    }

    /**
     * Load every requested pair in one query, keyed the same way as the request
     * map, so the delete stays a single round trip.
     *
     * @param array $requested
     * @phpstan-param array<string, array{index: int, sku: string, source: string}> $requested
     * @return array<string, SourceItemInterface>
     */
    private function loadExisting(array $requested): array
    {
        if ($requested === []) {
            return [];
        }

        $skus = [];
        $sources = [];
        foreach ($requested as $meta) {
            $skus[$meta['sku']] = $meta['sku'];
            $sources[$meta['source']] = $meta['source'];
        }

        $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, array_values($skus), 'in');
        $this->searchCriteriaBuilder->addFilter(
            SourceItemInterface::SOURCE_CODE,
            array_values($sources),
            'in'
        );

        $found = [];
        foreach ($this->sourceItemRepository->getList($this->searchCriteriaBuilder->create())->getItems() as $item) {
            $key = (string) $item->getSku() . "\0" . (string) $item->getSourceCode();
            if (isset($requested[$key])) {
                $found[$key] = $item;
            }
        }

        return $found;
    }
}
