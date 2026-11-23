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

class ScheduleResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'schedule';

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
        return 30;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'from_date' => $rule->getFromDate() === null ? null : (string) $rule->getFromDate(),
            'to_date' => $rule->getToDate() === null ? null : (string) $rule->getToDate(),
        ];
    }
}
