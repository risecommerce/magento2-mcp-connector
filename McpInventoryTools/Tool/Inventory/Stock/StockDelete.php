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
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;

/**
 * MCP write tool `inventory.stock.delete`.
 *
 * Refuses to delete the default stock, and refuses a stock that still serves a
 * website — deleting one out from under a live storefront zeroes availability
 * across that whole site.
 */
class StockDelete implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock.delete';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_delete';

    /**
     * @param StockRepositoryInterface $stockRepository
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannels
     */
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannels
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
        return 'Delete Stock';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Permanently delete an inventory stock. The default stock cannot '
            . 'be deleted, and neither can a stock that still has websites '
            . 'assigned — reassign those first with '
            . '`inventory.stock.set_sales_channels`, otherwise the storefront '
            . 'loses all availability.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to delete.')
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
        return 'Magento_InventoryApi::stock_delete';
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

        if ($stockId === $this->defaultStockProvider->getId()) {
            throw new LocalizedException(__('The default stock cannot be deleted.'));
        }

        $stock = $this->stockRepository->get($stockId);
        $name = (string) $stock->getName();

        $channels = $this->getAssignedSalesChannels->execute($stockId);
        if ($channels !== []) {
            $codes = [];
            foreach ($channels as $channel) {
                $codes[] = (string) $channel->getCode();
            }

            throw new LocalizedException(__(
                'Stock %1 still serves website(s): %2. Reassign them before deleting.',
                $stockId,
                implode(', ', $codes)
            ));
        }

        $this->stockRepository->deleteById($stockId);

        $payload = [
            'deleted' => true,
            'stock_id' => $stockId,
            'name' => $name,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode delete result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['stock_id' => $stockId, 'deleted' => true]
        );
    }
}
