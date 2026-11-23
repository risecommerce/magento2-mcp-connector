<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Marketing\CartRule;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\BooleanBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\MarketingEntityFinder;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Api\RuleRepositoryInterface;

/**
 * Idempotent toggle of the cart rule's `is_active` flag. No confirmation.
 */
class CartRuleSetActive implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'marketing.cart_rule.set_active';
    public const ACL_RESOURCE = 'Risecommerce_McpMarketingTools::tool_marketing_cart_rule_set_active';

    /**
     * @param MarketingEntityFinder $entityFinder
     * @param RuleRepositoryInterface $cartRuleRepository
     */
    public function __construct(
        private readonly MarketingEntityFinder $entityFinder,
        private readonly RuleRepositoryInterface $cartRuleRepository
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
        return 'Activate / Deactivate Cart Price Rule';
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Set a cart price rule\'s `is_active` flag. Disabling a rule '
            . 'immediately stops it from applying to new carts; existing '
            . 'orders are unaffected.';
    }

    /** @inheritDoc */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('rule_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->required()
                ->description('Numeric salesrule.rule_id.'))
            ->boolean('is_active', fn (BooleanBuilder $b) => $b
                ->required()
                ->description('Target state: true to enable, false to disable.'))
            ->toArray();
    }

    /** @inheritDoc */
    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    /** @inheritDoc */
    public function getUnderlyingAclResource(): ?string
    {
        return 'Magento_SalesRule::quote';
    }

    /** @inheritDoc */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::WRITE;
    }

    /** @inheritDoc */
    public function getConfirmationRequired(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function execute(array $arguments): ToolResultInterface
    {
        if (!array_key_exists('is_active', $arguments) || !is_bool($arguments['is_active'])) {
            throw new LocalizedException(__('"is_active" is required and must be a boolean.'));
        }

        $rule = $this->entityFinder->cartRuleFrom($arguments);
        $rule->setIsActive($arguments['is_active']);
        $saved = $this->cartRuleRepository->save($rule);

        $payload = [
            'rule_id' => (int) $saved->getRuleId(),
            'is_active' => (bool) $saved->getIsActive(),
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode set_active result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: $payload
        );
    }
}
