<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpMarketingTools\Model\FieldResolver\CartRule;

use Risecommerce\McpMarketingTools\Api\CartRuleFieldResolverInterface;
use Magento\SalesRule\Api\Data\RuleInterface;

/**
 * Action slice — simple_action, discount_amount, discount_qty, discount_step,
 * apply_to_shipping, simple_free_shipping, stop_rules_processing, sort_order.
 */
class ActionResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'action';

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return 50;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'simple_action' => $rule->getSimpleAction() === null ? null : (string) $rule->getSimpleAction(),
            'discount_amount' => (float) $rule->getDiscountAmount(),
            'discount_qty' => $rule->getDiscountQty() === null ? null : (float) $rule->getDiscountQty(),
            'discount_step' => (int) $rule->getDiscountStep(),
            'apply_to_shipping' => (bool) $rule->getApplyToShipping(),
            'simple_free_shipping' => $rule->getSimpleFreeShipping() === null
                ? null
                : (string) $rule->getSimpleFreeShipping(),
            'stop_rules_processing' => (bool) $rule->getStopRulesProcessing(),
            'sort_order' => (int) $rule->getSortOrder(),
        ];
    }
}
