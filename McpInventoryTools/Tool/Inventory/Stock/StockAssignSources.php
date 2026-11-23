<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\Stock;

use Exception;
use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ObjectBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\ServiceErrorMessage;
use Risecommerce\McpInventoryTools\Model\StockPayload;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterfaceFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;

/**
 * MCP write tool `inventory.stock.assign_sources`. Links sources to a stock and
 * sets their priority, which is the order MSI ships from.
 */
class StockAssignSources implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock.assign_sources';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_assign_sources';

    public const MAX_ITEMS = 100;

    /**
     * @param StockSourceLinksSaveInterface $stockSourceLinksSave
     * @param StockSourceLinkInterfaceFactory $linkFactory
     * @param StockRepositoryInterface $stockRepository
     * @param StockPayload $stockPayload
     * @param ServiceErrorMessage $serviceErrorMessage
     */
    public function __construct(
        private readonly StockSourceLinksSaveInterface $stockSourceLinksSave,
        private readonly StockSourceLinkInterfaceFactory $linkFactory,
        private readonly StockRepositoryInterface $stockRepository,
        private readonly StockPayload $stockPayload,
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
        return 'Assign Sources To Stock';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Link sources to a stock, so the stock aggregates their quantities. '
            . '`priority` sets the order Magento prefers sources when shipping — '
            . 'lower runs first. Re-assigning a source that is already linked '
            . 'updates its priority.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to link sources to.')
                ->required())
            ->array('sources', fn (ArrayBuilder $a) => $a
                ->ofObjects(fn (ObjectBuilder $o) => $o
                    ->string('source_code', fn (StringBuilder $s) => $s
                        ->minLength(1)
                        ->description('Source to link.')
                        ->required())
                    ->integer('priority', fn (IntegerBuilder $i) => $i
                        ->minimum(1)
                        ->description('Shipping preference order; lower is preferred. Defaults to 1.')))
                ->minItems(1)
                ->maxItems(self::MAX_ITEMS)
                ->description('Sources to link.')
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
        return 'Magento_InventoryApi::stock_source_link';
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
        $stockId = isset($arguments['stock_id']) && is_numeric($arguments['stock_id'])
            ? (int) $arguments['stock_id']
            : 0;
        if ($stockId <= 0) {
            throw new LocalizedException(__('"stock_id" is required.'));
        }

        $sources = $arguments['sources'] ?? null;
        if (!is_array($sources) || $sources === []) {
            throw new LocalizedException(__('"sources" must be a non-empty array.'));
        }

        // Fails if the stock does not exist, before any link is written.
        $this->stockRepository->get($stockId);

        $links = [];
        foreach ($sources as $source) {
            if (!is_array($source) || !isset($source['source_code']) || !is_scalar($source['source_code'])) {
                throw new LocalizedException(__('Each entry in "sources" requires a "source_code".'));
            }

            $sourceCode = (string) $source['source_code'];
            if ($sourceCode === '') {
                throw new LocalizedException(__('"source_code" cannot be empty.'));
            }

            /** @var StockSourceLinkInterface $link */
            $link = $this->linkFactory->create();
            $link->setStockId($stockId);
            $link->setSourceCode($sourceCode);
            $link->setPriority(
                isset($source['priority']) && is_numeric($source['priority'])
                    ? (int) $source['priority']
                    : 1
            );

            $links[] = $link;
        }

        try {
            $this->stockSourceLinksSave->execute($links);
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Failed to assign sources: %1', $this->serviceErrorMessage->from($e)),
                $e
            );
        }

        $payload = $this->stockPayload->toArray($this->stockRepository->get($stockId));

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode stock as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'stock_id' => $stockId,
                'assigned' => count($links),
            ]
        );
    }
}
