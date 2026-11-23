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
use Magento\InventoryApi\Api\StockSourceLinksDeleteInterface;

/**
 * MCP write tool `inventory.stock.unassign_sources`. Unlinks sources from a
 * stock; their quantities stop counting toward it.
 */
class StockUnassignSources implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock.unassign_sources';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_unassign_sources';

    public const MAX_ITEMS = 100;

    /**
     * @param StockSourceLinksDeleteInterface $stockSourceLinksDelete
     * @param StockSourceLinkInterfaceFactory $linkFactory
     * @param StockRepositoryInterface $stockRepository
     * @param StockPayload $stockPayload
     * @param ServiceErrorMessage $serviceErrorMessage
     */
    public function __construct(
        private readonly StockSourceLinksDeleteInterface $stockSourceLinksDelete,
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
        return 'Unassign Sources From Stock';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Unlink sources from a stock. Their quantities stop counting '
            . 'toward that stock, which lowers salable quantity on every website '
            . 'the stock serves. Source items and their quantities are untouched.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to unlink sources from.')
                ->required())
            ->array('source_codes', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->minItems(1)
                ->maxItems(self::MAX_ITEMS)
                ->uniqueItems()
                ->description('Source codes to unlink.')
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

        $sourceCodes = $arguments['source_codes'] ?? null;
        if (!is_array($sourceCodes) || $sourceCodes === []) {
            throw new LocalizedException(__('"source_codes" must be a non-empty array.'));
        }

        $links = [];
        $codes = [];
        foreach ($sourceCodes as $sourceCode) {
            if (!is_scalar($sourceCode) || (string) $sourceCode === '') {
                throw new LocalizedException(__('"source_codes" entries must be non-empty strings.'));
            }

            /** @var StockSourceLinkInterface $link */
            $link = $this->linkFactory->create();
            $link->setStockId($stockId);
            $link->setSourceCode((string) $sourceCode);

            $links[] = $link;
            $codes[] = (string) $sourceCode;
        }

        try {
            $this->stockSourceLinksDelete->execute($links);
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Failed to unassign sources: %1', $this->serviceErrorMessage->from($e)),
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
                'unassigned' => $codes,
            ]
        );
    }
}
