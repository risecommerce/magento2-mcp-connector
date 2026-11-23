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

class ScopeResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'scope';

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
        return 20;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'website_ids' => array_map(static fn ($v): int => (int) $v, $rule->getWebsiteIds()),
            'customer_group_ids' => array_map(static fn ($v): int => (int) $v, $rule->getCustomerGroupIds()),
        ];
    }
}
