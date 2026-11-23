<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Tool\Sales\Order;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Filters;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Pagination;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Sort;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpOrderTools\Model\Search\OrderItemQuery;
use Magento\Framework\Exception\LocalizedException;

/**
 * MCP tool `sales.order.item.list` — filtered + paged list of order line
 * items across orders, with an optional GROUP BY aggregate mode.
 */
class OrderItemList implements ToolInterface
{
    public const TOOL_NAME = 'sales.order.item.list';
    public const ACL_RESOURCE = 'Risecommerce_McpOrderTools::tool_sales_order_item_list';

    /**
     * @param OrderItemQuery $query
     */
    public function __construct(
        private readonly OrderItemQuery $query
    ) {
    }

    /** @inheritDoc */
    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    /** @inheritDoc */
    public function getTitle(): string
    {
        return 'List Order Items';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Search individual order line items across orders — the bulk '
            . 'alternative to calling `sales.order.get` once per order. '
            . 'Filter by order status/state, order date range, SKU (exact, '
            . '`*glob*`, or array), product type, store or website. Pass '
            . '`group_by` (e.g. ["sku"]) to aggregate instead of listing: '
            . 'returns summed qty_ordered / row_total plus order_count and '
            . 'line_count per group, answering "units sold per SKU" style '
            . 'questions in a single call.';
    }

    /** @inheritDoc */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->with(Filters::describing(
                'Filter clauses. Scalar or array values (array ⇒ IN); '
                . 'created_at_* bound the order placement window.',
                [
                    'order_status' => ['type' => ['string', 'array'], 'description' => 'Parent order status code(s).'],
                    'order_state' => ['type' => ['string', 'array'], 'description' => 'Parent order state code(s).'],
                    'created_at_from' => ['type' => 'string', 'description' => 'Order placement ISO date/datetime lower bound.'],
                    'created_at_to' => ['type' => 'string', 'description' => 'Order placement ISO date/datetime upper bound.'],
                    'sku' => ['type' => ['string', 'array'], 'description' => 'SKU: exact, `*glob*` wildcard, or array ⇒ IN.'],
                    'product_type' => ['type' => ['string', 'array'], 'description' => 'Product type code(s), e.g. "simple".'],
                    'store_id' => ['type' => ['integer', 'array'], 'description' => 'Store view id(s).'],
                    'website_id' => ['type' => ['integer', 'array'], 'description' => 'Website id(s), expanded to store-view ids.'],
                    'parent_items_only' => [
                        'type' => 'boolean',
                        'description' => 'Default true — drops bundle/configurable child lines so quantities are not double-counted.',
                    ],
                ]
            ))
            ->array('group_by', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s
                    ->enum(OrderItemQuery::GROUP_BY_FIELDS))
                ->minItems(1)
                ->description(
                    'Aggregate mode: group matching items by these keys and '
                    . 'return SUM(qty_ordered), SUM(row_total), order_count '
                    . 'and line_count per group instead of raw item rows.'
                ))
            ->with(Sort::fields(
                array_values(array_unique(array_merge(
                    OrderItemQuery::SORTABLE_FIELDS,
                    OrderItemQuery::AGGREGATE_SORTABLE_FIELDS
                )))
            ))
            ->with(Pagination::maxPageSize(OrderItemQuery::MAX_PAGE_SIZE))
            ->toArray();
    }

    /** @inheritDoc */
    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    /** @inheritDoc */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::READ;
    }

    /** @inheritDoc */
    public function getConfirmationRequired(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function execute(array $arguments): ToolResultInterface
    {
        $groupBy = $arguments['group_by'] ?? null;
        if (is_array($groupBy) && $groupBy !== []) {
            $result = $this->query->aggregate($arguments);
            $payload = [
                'groups' => $result['rows'],
                'group_by' => $result['group_by'],
                'page_size' => $result['page_size'],
                'current_page' => $result['page'],
            ];
            $summary = [
                'mode' => 'aggregate',
                'row_count' => count($result['rows']),
                'group_by' => $result['group_by'],
                'page' => $result['page'],
                'page_size' => $result['page_size'],
            ];
        } else {
            $result = $this->query->list($arguments);
            $payload = [
                'items' => $result['rows'],
                'total_count' => $result['total_count'],
                'page_size' => $result['page_size'],
                'current_page' => $result['page'],
            ];
            $summary = [
                'mode' => 'list',
                'row_count' => count($result['rows']),
                'total_count' => $result['total_count'],
                'page' => $result['page'],
                'page_size' => $result['page_size'],
            ];
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode order item list as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: $summary
        );
    }
}
