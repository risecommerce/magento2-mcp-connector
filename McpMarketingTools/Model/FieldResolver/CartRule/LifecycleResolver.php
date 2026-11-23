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
 * Lifecycle slice — `is_active`. The salesrule schema has no timestamps.
 */
class LifecycleResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'lifecycle';

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
        return 90;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'is_active' => (bool) $rule->getIsActive(),
        ];
    }
}
