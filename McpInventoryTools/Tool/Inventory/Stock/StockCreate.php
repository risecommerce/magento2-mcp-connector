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
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\StockPayload;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;

/**
 * MCP write tool `inventory.stock.create`.
 */
class StockCreate implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock.create';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_create';

    /**
     * @param StockRepositoryInterface $stockRepository
     * @param StockInterfaceFactory $stockFactory
     * @param StockPayload $stockPayload
     */
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly StockInterfaceFactory $stockFactory,
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
        return 'Create Stock';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Create an inventory stock. A new stock sells nothing until you '
            . 'link sources with `inventory.stock.assign_sources` and assign '
            . 'websites with `inventory.stock.set_sales_channels`.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('name', fn (StringBuilder $s) => $s
                ->minLength(1)->maxLength(255)
                ->description('Display name, e.g. "EU Stock".')
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
        $name = isset($arguments['name']) && is_string($arguments['name']) ? trim($arguments['name']) : '';
        if ($name === '') {
            throw new LocalizedException(__('"name" is required.'));
        }

        /** @var StockInterface $stock */
        $stock = $this->stockFactory->create();
        $stock->setName($name);

        $stockId = $this->stockRepository->save($stock);

        $payload = $this->stockPayload->toArray($this->stockRepository->get($stockId));

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode created stock as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['stock_id' => $stockId, 'name' => $name]
        );
    }
}
