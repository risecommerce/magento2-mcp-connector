<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\CatalogRule;

use Risecommerce\Mcp\Api\CatalogRuleFieldResolverInterface;
use Magento\CatalogRule\Model\Rule;

/**
 * Action slice — simple_action, discount_amount, sort_order, stop_rules_processing.
 */
class ActionResolver implements CatalogRuleFieldResolverInterface
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
    public function resolve(Rule $rule, array $args): array
    {
        unset($args);
        return [
            'simple_action' => (string) $rule->getSimpleAction(),
            'discount_amount' => (float) $rule->getDiscountAmount(),
            'sort_order' => $rule->getSortOrder() === null ? null : (int) $rule->getSortOrder(),
            'stop_rules_processing' => $rule->getStopRulesProcessing() === null
                ? null
                : (bool) $rule->getStopRulesProcessing(),
        ];
    }
}
