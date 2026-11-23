<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpReportTools\Tool\Customers;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpReportTools\Model\Search\CustomerReportSearchBuilder;
use Risecommerce\McpReportTools\Model\Support\RowSerializer;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Shared scaffolding for customer-oriented live reports
 * (`reports.customers.orders`, `.totals`, `.new`).
 *
 * Subclasses override `createCollection()` and inject their own auto-factory
 * to keep the wiring compile-time visible — no `ObjectManager` required.
 */
abstract class AbstractCustomerReportTool implements ToolInterface
{
    public function __construct(
        private readonly CustomerReportSearchBuilder $searchBuilder,
        private readonly RowSerializer $serializer
    ) {
    }

    /**
     * Build a fresh customer-report collection. Implementations should
     * inject the matching Magento auto-factory and return `$factory->create()`.
     */
    abstract protected function createCollection(): Collection;

    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('from', fn (StringBuilder $s) => $s->required()
                ->description('Start date (YYYY-MM-DD).'))
            ->string('to', fn (StringBuilder $s) => $s->required()
                ->description('End date (YYYY-MM-DD).'))
            ->integer('store_id', fn (IntegerBuilder $i) => $i->minimum(0)
                ->description('Limit to one store view id. Omit for all stores.'))
            ->integer('page', fn (IntegerBuilder $i) => $i->minimum(1)
                ->description('1-based page number.'))
            ->integer('page_size', fn (IntegerBuilder $i) => $i->minimum(1)
                ->maximum(CustomerReportSearchBuilder::MAX_PAGE_SIZE)
                ->description(sprintf(
                    'Rows per page (capped at %d).',
                    CustomerReportSearchBuilder::MAX_PAGE_SIZE
                )))
            ->toArray();
    }

    public function getWriteMode(): WriteMode
    {
        return WriteMode::READ;
    }

    public function getConfirmationRequired(): bool
    {
        return false;
    }

    public function execute(array $arguments): ToolResultInterface
    {
        $collection = $this->createCollection();
        $meta = $this->searchBuilder->apply($collection, $arguments);

        $rows = [];
        /** @var \Magento\Framework\DataObject $item */
        foreach ($collection->getItems() as $item) {
            $data = $item->getData();
            if (is_array($data)) {
                $rows[] = $this->serializer->normalise($data);
            }
        }

        $payload = [
            'items' => $rows,
            'total_count' => $collection->getSize(),
            'page' => $meta['page'],
            'page_size' => $meta['page_size'],
            'from' => $meta['from'],
            'to' => $meta['to'],
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode %1 as JSON.', $this->getName()));
        }
        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'row_count' => count($rows),
                'total_count' => $collection->getSize(),
                'from' => $meta['from'],
                'to' => $meta['to'],
                'page' => $meta['page'],
            ]
        );
    }
}
