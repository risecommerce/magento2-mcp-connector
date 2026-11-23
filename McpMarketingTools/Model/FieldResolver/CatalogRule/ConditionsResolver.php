<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpMarketingTools\Model\FieldResolver\CatalogRule;

use Risecommerce\McpMarketingTools\Api\CatalogRuleFieldResolverInterface;
use Risecommerce\McpMarketingTools\Model\Condition\CatalogRuleConditionSerializer;
use Magento\CatalogRule\Model\Rule;
/**
 * `conditions` slice — recursive condition tree.
 */
class ConditionsResolver implements CatalogRuleFieldResolverInterface
{
    public const KEY = 'conditions';

    /**
     * @param CatalogRuleConditionSerializer $serializer
     */
    public function __construct(
        private readonly CatalogRuleConditionSerializer $serializer
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
    public function resolve(Rule $rule, array $args): array
    {
        unset($args);
        return $this->serializer->toJson($rule->getRuleCondition()) ?? [];
    }
}
