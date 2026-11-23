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

class IdentityResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'identity';

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
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'rule_id' => (int) $rule->getRuleId(),
            'name' => $rule->getName() === null ? null : (string) $rule->getName(),
            'description' => $rule->getDescription() === null ? null : (string) $rule->getDescription(),
            'is_advanced' => (bool) $rule->getIsAdvanced(),
        ];
    }
}
