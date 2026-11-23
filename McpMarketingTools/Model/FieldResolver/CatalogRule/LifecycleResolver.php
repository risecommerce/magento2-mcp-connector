<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpMarketingTools\Model\FieldResolver\CatalogRule;

use Risecommerce\McpMarketingTools\Api\CatalogRuleFieldResolverInterface;
use Magento\CatalogRule\Model\Rule;

/**
 * Lifecycle slice — `is_active`. The catalogrule schema has no timestamps.
 */
class LifecycleResolver implements CatalogRuleFieldResolverInterface
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
        return 60;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Rule $rule, array $args): array
    {
        unset($args);
        return [
            'is_active' => (bool) $rule->getIsActive(),
        ];
    }
}
