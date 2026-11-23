<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Model\FieldResolver\Rule;

use Risecommerce\McpTaxTools\Api\RuleFieldResolverInterface;
use Magento\Tax\Api\Data\TaxRuleInterface;

class IdentityResolver implements RuleFieldResolverInterface
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
    public function resolve(TaxRuleInterface $rule, array $args): array
    {
        unset($args);
        return [
            'id' => $rule->getId() !== null ? (int) $rule->getId() : null,
            'code' => (string) $rule->getCode(),
            'priority' => (int) $rule->getPriority(),
            'position' => (int) $rule->getPosition(),
            'calculate_subtotal' => (bool) $rule->getCalculateSubtotal(),
        ];
    }
}
