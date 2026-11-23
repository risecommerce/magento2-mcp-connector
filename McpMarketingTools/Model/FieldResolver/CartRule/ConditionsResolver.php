<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpMarketingTools\Model\FieldResolver\CartRule;

use Risecommerce\McpMarketingTools\Api\CartRuleFieldResolverInterface;
use Risecommerce\McpMarketingTools\Model\Condition\SalesRuleConditionSerializer;
use Magento\SalesRule\Api\Data\RuleInterface;
/**
 * `condition` slice — the "if cart matches" tree.
 */
class ConditionsResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'condition';

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
        return 40;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return $this->serializer->toJson($rule->getCondition()) ?? [];
    }
}
