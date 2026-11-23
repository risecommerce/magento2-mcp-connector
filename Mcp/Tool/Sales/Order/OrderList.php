<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Sales\Order;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\FieldSelection;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Filters;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Pagination;
use Risecommerce\Mcp\Model\Tool\Schema\Preset\Sort;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Api\OrderFieldResolverInterface;
use Risecommerce\Mcp\Model\FieldResolver\Order\OnDemandResolver;
use Risecommerce\Mcp\Model\Search\OrderSearchCriteriaBuilder;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * MCP tool `sales.order.list` — filtered + paged list of orders.
 */
class OrderList implements ToolInterface
{
    public const TOOL_NAME = 'sales.order.list';
    public const ACL_RESOURCE = 'Risecommerce_McpOrderTools::tool_sales_order_list';

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSearchCriteriaBuilder $searchBuilder
     * @param ResolverPipeline $pipeline
     * @param OrderFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderSearchCriteriaBuilder $searchBuilder,
        private readonly ResolverPipeline $pipeline,
        private readonly array $fieldResolvers = []
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
        return 'List Orders';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Search sales orders with optional filters (status, state, date '
            . 'range, grand-total range, customer email, increment id, store '
            . 'id, website id) and paging. Each result row is composed from '
            . 'the same field resolvers as `sales.order.get`; use '
            . '`fields`/`exclude` to narrow. Line items are available on '
            . 'request: include "items" in `fields` (kept out of default '
            . 'rows for payload size). For item-level filtering or '
            . 'aggregation prefer `sales.order.item.list`.';
    }

    /** @inheritDoc */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->with(Filters::describing(
                'Filter clauses. Scalar or array values (array ⇒ IN). '
                . '`website_id` is expanded to the matching store-view ids — '
                . 'orders carry no website column.',
                [
                    'status' => ['type' => ['string', 'array'], 'description' => 'Order status code(s), e.g. "processing".'],
                    'state' => ['type' => ['string', 'array'], 'description' => 'Order state code(s).'],
                    'increment_id' => ['type' => ['string', 'array'], 'description' => 'Human-facing order number(s).'],
                    'customer_email' => ['type' => ['string', 'array'], 'description' => 'Exact customer email(s).'],
                    'store_id' => ['type' => ['integer', 'array'], 'description' => 'Store view id(s).'],
                    'website_id' => ['type' => ['integer', 'array'], 'description' => 'Website id(s).'],
                    'created_at_from' => ['type' => 'string', 'description' => 'ISO date/datetime lower bound.'],
                    'created_at_to' => ['type' => 'string', 'description' => 'ISO date/datetime upper bound.'],
                    'grand_total_min' => ['type' => 'number', 'description' => 'Minimum grand total.'],
                    'grand_total_max' => ['type' => 'number', 'description' => 'Maximum grand total.'],
                ]
            ))
            ->with(Sort::fields(OrderSearchCriteriaBuilder::SORTABLE_FIELDS))
            ->with(Pagination::maxPageSize(OrderSearchCriteriaBuilder::MAX_PAGE_SIZE))
            ->with(FieldSelection::default())
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
        $criteria = $this->searchBuilder->build($arguments);
        $result = $this->orderRepository->getList($criteria);

        $plan = $this->pipeline->plan($this->requestedResolvers($arguments), $arguments);

        $rows = [];
        foreach ($result->getItems() as $order) {
            $row = [];
            foreach ($plan as $resolver) {
                $row[$resolver->getKey()] = $resolver->resolve($order, $arguments);
            }
            $rows[] = $row;
        }

        $payload = [
            'items' => $rows,
            'total_count' => (int) $result->getTotalCount(),
            'page_size' => $criteria->getPageSize() ?? OrderSearchCriteriaBuilder::DEFAULT_PAGE_SIZE,
            'current_page' => $criteria->getCurrentPage() ?? 1,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode order list as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'row_count' => count($rows),
                'total_count' => (int) $result->getTotalCount(),
                'page' => $criteria->getCurrentPage(),
                'page_size' => $criteria->getPageSize(),
            ]
        );
    }

    /**
     * Drops opt-in resolvers whose key was not explicitly requested via
     * `fields`, so expensive slices don't run for default list calls.
     *
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @return OrderFieldResolverInterface[]
     */
    private function requestedResolvers(array $arguments): array
    {
        $resolvers = [];
        foreach ($this->fieldResolvers as $resolver) {
            if ($resolver instanceof OnDemandResolver && !$resolver->isRequested($arguments)) {
                continue;
            }
            $resolvers[] = $resolver;
        }

        return $resolvers;
    }
}
