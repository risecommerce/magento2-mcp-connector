<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\SourceItem;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Pagination;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

/**
 * MCP read tool `inventory.source_item.list`. Per-source quantities.
 *
 * Filters by SKU in one repository call rather than looping — the same N+1
 * trap the catalog read tools already avoid.
 */
class SourceItemList implements ToolInterface
{
    public const TOOL_NAME = 'inventory.source_item.list';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_source_item_list';

    private const MAX_PAGE_SIZE = 500;
    private const DEFAULT_PAGE_SIZE = 100;
    private const MAX_SKUS = 200;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly SourceItemRepositoryInterface $sourceItemRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
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
        return 'List Source Items';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'List per-source stock rows — how much of each SKU sits at each '
            . 'source. Filter by `sku` (a list is accepted, resolved in one query), '
            . 'by `source_code`, or by `status`. This is on-hand quantity; for what '
            . 'is actually sellable after reservations use `inventory.salable_qty.get`.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->array('sku', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->maxItems(self::MAX_SKUS)
                ->uniqueItems()
                ->description('One or more SKUs to report on.'))
            ->string('source_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Limit to a single source.'))
            ->integer('status', fn (IntegerBuilder $i) => $i
                ->enum([0, 1])
                ->description('1 = in stock, 0 = out of stock.'))
            ->with(Pagination::maxPageSize(self::MAX_PAGE_SIZE))
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
        $page = isset($arguments['page']) && is_numeric($arguments['page'])
            ? max(1, (int) $arguments['page'])
            : 1;
        $pageSize = isset($arguments['page_size']) && is_numeric($arguments['page_size'])
            ? min(self::MAX_PAGE_SIZE, max(1, (int) $arguments['page_size']))
            : self::DEFAULT_PAGE_SIZE;

        $this->searchCriteriaBuilder->setCurrentPage($page);
        $this->searchCriteriaBuilder->setPageSize($pageSize);

        $skus = $this->skuFilter($arguments);
        if ($skus !== []) {
            $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $skus, 'in');
        }
        if (isset($arguments['source_code']) && is_string($arguments['source_code'])) {
            $this->searchCriteriaBuilder->addFilter(
                SourceItemInterface::SOURCE_CODE,
                $arguments['source_code']
            );
        }
        if (isset($arguments['status']) && is_numeric($arguments['status'])) {
            $this->searchCriteriaBuilder->addFilter(
                SourceItemInterface::STATUS,
                (int) $arguments['status']
            );
        }

        $result = $this->sourceItemRepository->getList($this->searchCriteriaBuilder->create());

        $items = [];
        foreach ($result->getItems() as $sourceItem) {
            $items[] = [
                'sku' => (string) $sourceItem->getSku(),
                'source_code' => (string) $sourceItem->getSourceCode(),
                'quantity' => (float) $sourceItem->getQuantity(),
                'status' => (int) $sourceItem->getStatus(),
            ];
        }

        $payload = [
            'total_count' => (int) $result->getTotalCount(),
            'page' => $page,
            'page_size' => $pageSize,
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode source item list as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'total_count' => (int) $result->getTotalCount(),
                'returned' => count($items),
            ]
        );
    }

    /**
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @return string[]
     */
    private function skuFilter(array $arguments): array
    {
        if (!isset($arguments['sku']) || !is_array($arguments['sku'])) {
            return [];
        }

        $skus = [];
        foreach ($arguments['sku'] as $sku) {
            if (is_scalar($sku) && (string) $sku !== '') {
                $skus[] = (string) $sku;
            }
        }

        return array_values(array_unique($skus));
    }
}
