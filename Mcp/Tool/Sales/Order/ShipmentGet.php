<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Sales\Order;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Api\ShipmentFieldResolverInterface;
use Risecommerce\Mcp\Model\OrderEntityFinder;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Magento\Framework\Exception\LocalizedException;

/**
 * MCP tool `sales.order.shipment.get` — fetch one shipment by id or increment id.
 */
class ShipmentGet implements ToolInterface
{
    public const TOOL_NAME = 'sales.order.shipment.get';
    public const ACL_RESOURCE = 'Risecommerce_McpOrderTools::tool_sales_order_shipment_get';

    /**
     * @param OrderEntityFinder $entityFinder
     * @param ResolverPipeline $pipeline
     * @param ShipmentFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly OrderEntityFinder $entityFinder,
        private readonly ResolverPipeline $pipeline,
        private readonly array $fieldResolvers = []
    ) {
    }

    /** @inheritDoc */
    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    /** @inheritDoc */
    public function getTitle(): string
    {
        return 'Get Shipment';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Fetch a single shipment by its numeric id or increment id.';
    }

    /** @inheritDoc */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('shipment_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Numeric sales_shipment.entity_id.')
            )
            ->string('shipment_increment_id', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Shipment increment id.')
            )
            ->array('fields', fn (ArrayBuilder $a) => $a->ofStrings())
            ->array('exclude', fn (ArrayBuilder $a) => $a->ofStrings())
            ->toArray();
    }

    /** @inheritDoc */
    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    /** @inheritDoc */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::READ;
    }

    /** @inheritDoc */
    public function getConfirmationRequired(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function execute(array $arguments): ToolResultInterface
    {
        $shipment = $this->entityFinder->shipmentFrom($arguments);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($shipment, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode shipment as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'entity_id' => (int) $shipment->getEntityId(),
                'increment_id' => (string) $shipment->getIncrementId(),
                'order_id' => (int) $shipment->getOrderId(),
            ]
        );
    }
}
