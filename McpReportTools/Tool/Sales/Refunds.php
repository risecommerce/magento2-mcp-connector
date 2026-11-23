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
use Magento\Sales\Model\ResourceModel\Report\Refunded\Collection\RefundedFactory as RefundedReportCollectionFactory;

/**
 * MCP tool `reports.sales.refunds` — refund activity aggregated per period.
 * Mirrors admin *Reports → Sales → Refunds* with "Date Used" defaulted to
 * refund date. Reads `sales_refunded_aggregated`.
 */
class Refunds extends AbstractAggregatedReportTool
{
    public const TOOL_NAME = 'reports.sales.refunds';
    public const ACL_RESOURCE = 'Risecommerce_McpReportTools::mcp_tool_reports_sales_refunds';

    public function __construct(
        SalesReportSearchBuilder $searchBuilder,
        private readonly RefundedReportCollectionFactory $collectionFactory
    ) {
        parent::__construct($searchBuilder);
    }

    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    public function getTitle(): string
    {
        return 'Sales Report: Refunds';
    }

    public function getDescription(): string
    {
        return 'Refund activity aggregated per period — orders refunded, '
            . 'online vs offline refund totals. Date axis is the refund '
            . 'date. Reads pre-aggregated data; if numbers look stale run '
            . '`reports.statistics.refresh_recent` with '
            . '`report_codes=["refunded"]`.';
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
