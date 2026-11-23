<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\Stock;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Pagination;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockPayload;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * MCP read tool `inventory.stock.list`. Stocks aggregate sources and are what
 * websites actually sell from.
 */
class StockList implements ToolInterface
{
    public const TOOL_NAME = 'inventory.stock.list';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_list';

    private const MAX_PAGE_SIZE = 200;
    private const DEFAULT_PAGE_SIZE = 50;

    /**
     * @param StockRepositoryInterface $stockRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockPayload $stockPayload
     */
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StockPayload $stockPayload
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
        return 'List Stocks';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'List inventory stocks. A stock groups one or more sources and is '
            . 'assigned to the websites that sell from it — so a stock is what '
            . 'decides which warehouses serve which storefront. Each row includes '
            . 'its linked sources and assigned sales channels.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
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

        $result = $this->stockRepository->getList($this->searchCriteriaBuilder->create());

        $items = [];
        foreach ($result->getItems() as $stock) {
            $items[] = $this->stockPayload->toArray($stock);
        }

        $payload = [
            'total_count' => (int) $result->getTotalCount(),
            'page' => $page,
            'page_size' => $pageSize,
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode stock list as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'total_count' => (int) $result->getTotalCount(),
                'returned' => count($items),
            ]
        );
    }
}
