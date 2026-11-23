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
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\Mcp\Api\CreditMemoFieldResolverInterface;
use Risecommerce\Mcp\Model\OrderEntityFinder;
use Magento\Framework\Exception\LocalizedException;

/**
 * MCP tool `sales.order.credit_memo.get` — fetch one credit memo.
 */
class CreditMemoGet implements ToolInterface
{
    public const TOOL_NAME = 'sales.order.credit_memo.get';
    public const ACL_RESOURCE = 'Risecommerce_McpOrderTools::tool_sales_order_credit_memo_get';

    /**
     * @param OrderEntityFinder $entityFinder
     * @param ResolverPipeline $pipeline
     * @param CreditMemoFieldResolverInterface[] $fieldResolvers
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
        return 'Get Credit Memo';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Fetch a single credit memo by its numeric id or increment id.';
    }

    /** @inheritDoc */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('credit_memo_id', fn (IntegerBuilder $i) => $i->minimum(1))
            ->string('credit_memo_increment_id', fn (StringBuilder $s) => $s->minLength(1))
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
        $memo = $this->entityFinder->creditMemoFrom($arguments);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($memo, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode credit memo as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'entity_id' => (int) $memo->getEntityId(),
                'increment_id' => (string) $memo->getIncrementId(),
                'order_id' => (int) $memo->getOrderId(),
            ]
        );
    }
}
