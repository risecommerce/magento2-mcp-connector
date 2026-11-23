<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\CartRule;

use Risecommerce\Mcp\Api\CartRuleFieldResolverInterface;
use Magento\SalesRule\Api\Data\RuleInterface;

/**
 * Usage slice — times_used, is_rss.
 */
class UsageResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'usage';

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
        return 70;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'times_used' => (int) $rule->getTimesUsed(),
            'is_rss' => (bool) $rule->getIsRss(),
        ];
    }
}
