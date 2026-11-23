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
 * Coupon settings slice — coupon_type, use_auto_generation, uses_per_coupon,
 * uses_per_customer.
 */
class CouponSettingsResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'coupon_settings';

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
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'coupon_type' => (string) $rule->getCouponType(),
            'use_auto_generation' => (bool) $rule->getUseAutoGeneration(),
            'uses_per_coupon' => (int) $rule->getUsesPerCoupon(),
            'uses_per_customer' => (int) $rule->getUsesPerCustomer(),
        ];
    }
}
