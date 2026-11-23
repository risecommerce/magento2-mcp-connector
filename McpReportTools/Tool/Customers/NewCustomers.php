<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpReportTools\Tool\Customers;

use Risecommerce\McpReportTools\Model\Search\CustomerReportSearchBuilder;
use Risecommerce\McpReportTools\Model\Support\RowSerializer;
use Magento\Framework\Data\Collection;
use Magento\Reports\Model\ResourceModel\Accounts\CollectionFactory as AccountsReportCollectionFactory;

/**
 * MCP tool `reports.customers.new` — newly registered customer accounts per
 * period. Mirrors admin *Reports → Customers → New*. Lives in
 * `Tool/Customers/NewCustomers.php` because `new` is a PHP reserved word.
 */
class NewCustomers extends AbstractCustomerReportTool
{
    public const TOOL_NAME = 'reports.customers.new';
    public const ACL_RESOURCE = 'Risecommerce_McpReportTools::mcp_tool_reports_customers_new';

    public function __construct(
        CustomerReportSearchBuilder $searchBuilder,
        RowSerializer $serializer,
        private readonly AccountsReportCollectionFactory $collectionFactory
    ) {
        parent::__construct($searchBuilder, $serializer);
    }

    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    public function getTitle(): string
    {
        return 'Customer Report: New Accounts';
    }

    public function getDescription(): string
    {
        return 'Count of newly registered customer accounts per period in '
            . 'the given date range. Mirrors admin *Reports → Customers → '
            . 'New*. Live query against customer_entity.';
    }

    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    protected function createCollection(): Collection
    {
        return $this->collectionFactory->create();
    }
}
