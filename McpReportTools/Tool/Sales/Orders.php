<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpReportTools\Tool\Sales;

use Risecommerce\McpReportTools\Model\Search\SalesReportSearchBuilder;
use Risecommerce\McpReportTools\Tool\AbstractAggregatedReportTool;
use Magento\Reports\Model\ResourceModel\Report\Collection\AbstractCollection;
use Magento\Sales\Model\ResourceModel\Report\Order\CollectionFactory as OrderReportCollectionFactory;

/**
 * MCP tool `reports.sales.orders` — order sales aggregated per period.
 * Mirrors admin *Reports → Sales → Orders*. Reads the
 * `sales_order_aggregated_created` / `_updated` tables populated by
 * `reports.statistics.refresh_*` (or by admin *Refresh Statistics*).
 */
class Orders extends AbstractAggregatedReportTool
{
    public const TOOL_NAME = 'reports.sales.orders';
    public const ACL_RESOURCE = 'Risecommerce_McpReportTools::mcp_tool_reports_sales_orders';

    public function __construct(
        SalesReportSearchBuilder $searchBuilder,
        private readonly OrderReportCollectionFactory $collectionFactory
    ) {
        parent::__construct($searchBuilder);
    }

    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    public function getTitle(): string
    {
        return 'Sales Report: Orders';
    }

    public function getDescription(): string
    {
        return 'Order sales aggregated per period (day/month/year) — orders '
            . 'count, items sold, revenue, profit, tax, shipping, discount, '
            . 'cancellations, refunded. Reads pre-aggregated data; if '
            . 'numbers look stale run `reports.statistics.refresh_recent` '
            . 'first.';
    }

    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    protected function createCollection(): AbstractCollection
    {
        return $this->collectionFactory->create();
    }
}
