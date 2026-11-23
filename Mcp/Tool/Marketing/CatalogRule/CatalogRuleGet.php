<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Marketing\CatalogRule;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\Mcp\Api\CatalogRuleFieldResolverInterface;
use Risecommerce\Mcp\Model\MarketingEntityFinder;
use Magento\Framework\Exception\LocalizedException;

class CatalogRuleGet implements ToolInterface
{
    public const TOOL_NAME = 'marketing.catalog_rule.get';
    public const ACL_RESOURCE = 'Risecommerce_McpMarketingTools::tool_marketing_catalog_rule_get';

    /**
     * @param MarketingEntityFinder $entityFinder
     * @param ResolverPipeline $pipeline
     * @param CatalogRuleFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly MarketingEntityFinder $entityFinder,
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
        return 'Get Catalog Rule';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Fetch a single catalog price rule by its numeric rule_id. '
            . 'The response is composed from registered field resolvers — '
            . 'use the `fields` argument to narrow the payload or `exclude` '
            . 'to drop specific slices.';
    }

    /** @inheritDoc */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('rule_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->required()
                ->description('Numeric catalogrule.rule_id.')
            )
            ->array('fields', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description('Whitelist of resolver keys to include.'))
            ->array('exclude', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description('Resolver keys to drop from the default payload.'))
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
        $rule = $this->entityFinder->catalogRuleFrom($arguments);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($rule, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode catalog-rule payload as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'rule_id' => (int) $rule->getRuleId(),
                'name' => (string) $rule->getName(),
                'is_active' => (bool) $rule->getIsActive(),
            ]
        );
    }
}
