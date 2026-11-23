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
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockIdResolver;
use Risecommerce\McpInventoryTools\Model\StockPayload;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * MCP read tool `inventory.stock.get`.
 */
class StockGet implements ToolInterface
{
    public const TOOL_NAME = 'inventory.stock.get';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_get';

    /**
     * @param StockRepositoryInterface $stockRepository
     * @param StockIdResolver $stockIdResolver
     * @param StockPayload $stockPayload
     */
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly StockIdResolver $stockIdResolver,
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
        return 'Get Stock';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch one stock with its linked sources and assigned sales '
            . 'channels. Identify it by `stock_id`, or by `website_code` to look up '
            . 'whichever stock serves that website. With neither, the default stock '
            . 'is returned.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Numeric stock id.'))
            ->string('website_code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Website code; resolves to the stock serving it.'))
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
        $stockId = $this->stockIdResolver->resolve($arguments);
        $stock = $this->stockRepository->get($stockId);

        $payload = $this->stockPayload->toArray($stock);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode stock as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['stock_id' => $stockId]
        );
    }
}
