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
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Model\ReplaceSalesChannelsForStockInterface;

/**
 * MCP write tool `inventory.stock.set_sales_channels`.
 *
 * Replaces the whole assignment set for one stock. Websites left out are
 * unassigned outright — MSI does not fall them back to the default stock, so
 * they resolve to no stock and go unsalable. The tool echoes the resulting
 * state back because this reshapes availability store-wide.
 */
class StockSetSalesChannels implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.stock.set_sales_channels';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_stock_set_sales_channels';

    public const MAX_ITEMS = 100;

    /**
     * @param ReplaceSalesChannelsForStockInterface $replaceSalesChannels
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     * @param StockRepositoryInterface $stockRepository
     * @param StockPayload $stockPayload
     * @param ServiceErrorMessage $serviceErrorMessage
     */
    public function __construct(
        private readonly ReplaceSalesChannelsForStockInterface $replaceSalesChannels,
        private readonly SalesChannelInterfaceFactory $salesChannelFactory,
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
        return 'Set Stock Sales Channels';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Assign websites to a stock. This REPLACES the current assignment '
            . 'set for this stock: any website not in `website_codes` is left '
            . 'assigned to NO stock at all — it does not fall back to the default '
            . 'stock, and every product on that website stops being salable until '
            . 'you assign it somewhere. Pass the full intended list, not just the '
            . 'additions, and to move a website back to the default stock call '
            . 'this tool again on that stock with the website included.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('stock_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Stock to assign websites to.')
                ->required())
            ->array('website_codes', fn (ArrayBuilder $a) => $a
                ->ofStrings(fn (StringBuilder $s) => $s->minLength(1))
                ->maxItems(self::MAX_ITEMS)
                ->uniqueItems()
                ->description('Full list of website codes this stock should serve. '
                    . 'An empty array unassigns every website.')
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

        $websiteCodes = $arguments['website_codes'] ?? null;
        if (!is_array($websiteCodes)) {
            throw new LocalizedException(__('"website_codes" must be an array.'));
        }

        $this->stockRepository->get($stockId);

        $channels = [];
        foreach ($websiteCodes as $websiteCode) {
            if (!is_scalar($websiteCode) || (string) $websiteCode === '') {
                throw new LocalizedException(__('"website_codes" entries must be non-empty strings.'));
            }

            /** @var SalesChannelInterface $channel */
            $channel = $this->salesChannelFactory->create();
            $channel->setType(SalesChannelInterface::TYPE_WEBSITE);
            $channel->setCode((string) $websiteCode);

            $channels[] = $channel;
        }

        try {
            $this->replaceSalesChannels->execute($channels, $stockId);
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Failed to set sales channels: %1', $this->serviceErrorMessage->from($e)),
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
                'website_count' => count($channels),
            ]
        );
    }
}
