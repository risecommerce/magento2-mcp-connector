<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\CartRule;

use Risecommerce\Mcp\Api\CartRuleFieldResolverInterface;
use Risecommerce\Mcp\Model\Condition\SalesRuleConditionSerializer;
use Magento\SalesRule\Api\Data\RuleInterface;
/**
 * `action_condition` slice — the "apply discount to which items" tree.
 */
class ActionConditionResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'action_condition';

    /**
     * @param SalesRuleConditionSerializer $serializer
     */
    public function __construct(
        private readonly SalesRuleConditionSerializer $serializer
    ) {
    }

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
        return 45;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return $this->serializer->toJson($rule->getActionCondition()) ?? [];
    }
}
