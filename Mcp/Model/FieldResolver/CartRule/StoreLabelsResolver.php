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
use Magento\SalesRule\Api\Data\RuleLabelInterface;

/**
 * Store-view label overrides as a flat `{store_id, store_label}` list.
 */
class StoreLabelsResolver implements CartRuleFieldResolverInterface
{
    public const KEY = 'store_labels';

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
        return 80;
    }

    /**
     * @inheritDoc
     */
    public function resolve(RuleInterface $rule, array $args): array
    {
        unset($args);
        $labels = $rule->getStoreLabels();
        if (!is_array($labels)) {
            return [];
        }
        $rows = [];
        foreach ($labels as $label) {
            $rows[] = [
                'store_id' => (int) $label->getStoreId(),
                'store_label' => (string) $label->getStoreLabel(),
            ];
        }
        return $rows;
    }
}
