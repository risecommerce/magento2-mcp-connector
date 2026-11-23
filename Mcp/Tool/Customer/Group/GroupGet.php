<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Customer\Group;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\Mcp\Api\GroupFieldResolverInterface;
use Risecommerce\Mcp\Model\CustomerEntityFinder;
use Magento\Framework\Exception\LocalizedException;

class GroupGet implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'customer.group.get';
    public const ACL_RESOURCE = 'Risecommerce_McpCustomerTools::tool_customer_group_get';

    /**
     * @param CustomerEntityFinder $entityFinder
     * @param ResolverPipeline $pipeline
     * @param GroupFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly CustomerEntityFinder $entityFinder,
        private readonly ResolverPipeline $pipeline,
        private readonly array $fieldResolvers = []
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
        return 'Get Customer Group';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch a single customer group by numeric id. Group `0` is '
            . 'the reserved "NOT LOGGED IN" group.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('id', fn (IntegerBuilder $i) => $i->minimum(0)->required())
            ->array('fields', fn (ArrayBuilder $a) => $a->ofStrings()
                ->description('Whitelist of resolver keys to include — '
                    . 'overrides defaults.')
            )
            ->array('exclude', fn (ArrayBuilder $a) => $a->ofStrings()
                ->description('Resolver keys to omit from the response.')
            )
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
        return 'Magento_Customer::group';
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
        $group = $this->entityFinder->groupFrom($arguments);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($group, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode group payload as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'id' => $group->getId() !== null ? (int) $group->getId() : null,
                'code' => (string) $group->getCode(),
            ]
        );
    }
}
