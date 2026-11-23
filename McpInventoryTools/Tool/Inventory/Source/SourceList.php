<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\Source;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\BooleanBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Pagination;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\SourcePayload;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * MCP read tool `inventory.source.list`. Sources are the physical places stock
 * lives — warehouses, stores, drop-shippers.
 */
class SourceList implements ToolInterface
{
    public const TOOL_NAME = 'inventory.source.list';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_source_list';

    private const MAX_PAGE_SIZE = 200;
    private const DEFAULT_PAGE_SIZE = 50;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourcePayload $sourcePayload
     */
    public function __construct(
        private readonly SourceRepositoryInterface $sourceRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SourcePayload $sourcePayload
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
        return 'List Inventory Sources';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'List inventory sources — the physical locations that hold stock '
            . '(warehouses, retail stores, drop-shippers). Use this to discover '
            . 'valid `source_code` values before writing source items.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->boolean('enabled', fn (BooleanBuilder $b) => $b
                ->description('Filter by enabled flag. Omit to return both.'))
            ->string('country_id', fn (StringBuilder $s) => $s
                ->minLength(2)->maxLength(2)
                ->description('Two-letter country code, e.g. LV.'))
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

        if (array_key_exists('enabled', $arguments)) {
            $this->searchCriteriaBuilder->addFilter('enabled', $arguments['enabled'] ? 1 : 0);
        }
        if (isset($arguments['country_id']) && is_string($arguments['country_id'])) {
            $this->searchCriteriaBuilder->addFilter('country_id', $arguments['country_id']);
        }

        $result = $this->sourceRepository->getList($this->searchCriteriaBuilder->create());

        $items = [];
        foreach ($result->getItems() as $source) {
            $items[] = $this->sourcePayload->toArray($source);
        }

        $payload = [
            'total_count' => (int) $result->getTotalCount(),
            'page' => $page,
            'page_size' => $pageSize,
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode source list as JSON.'));
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
