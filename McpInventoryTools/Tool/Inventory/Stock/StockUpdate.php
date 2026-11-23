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
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockPayload;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * MCP write tool `inventory.stock.update`.
 */
class StockUpdate implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock.update';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_update';

    /**
     * @param StockRepositoryInterface $stockRepository
     * @param StockPayload $stockPayload
     */
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
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
        return 'Update Stock';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Rename an inventory stock. Source links and sales channels are '
            . 'managed by `inventory.stock.assign_sources`, '
            . '`inventory.stock.unassign_sources` and '
            . '`inventory.stock.set_sales_channels`.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to update.')
                ->required())
            ->string('name', fn (StringBuilder $s) => $s
                ->minLength(1)->maxLength(255)
                ->description('New display name.')
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
        return 'Magento_InventoryApi::stock_edit';
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

        $name = isset($arguments['name']) && is_string($arguments['name']) ? trim($arguments['name']) : '';
        if ($name === '') {
            throw new LocalizedException(__('"name" is required.'));
        }

        $stock = $this->stockRepository->get($stockId);
        $stock->setName($name);
        $this->stockRepository->save($stock);

        $payload = $this->stockPayload->toArray($this->stockRepository->get($stockId));

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode updated stock as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['stock_id' => $stockId, 'name' => $name]
        );
    }
}
