<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Condition;

use Magento\CatalogRule\Api\Data\ConditionInterface;

/**
 * Walks a catalog-rule condition tree into nested JSON-friendly arrays.
 */
class CatalogRuleConditionSerializer
{
    /**
     * @param ConditionInterface|null $node
     * @return array<string, mixed>|null
     */
    public function toJson(?ConditionInterface $node): ?array
    {
        if ($node === null) {
            return null;
        }

        // Combine vs leaf nodes leave different fields empty; cast through
        // string|"" so JSON shape stays stable across both kinds.
        $payload = [
            'type' => (string) $node->getType(),
            'attribute' => (string) $node->getAttribute(),
            'operator' => (string) $node->getOperator(),
            'value' => $node->getValue(),
            'is_value_parsed' => $node->getIsValueParsed(),
            'aggregator' => (string) $node->getAggregator(),
        ];

        $children = $node->getConditions();
        if (is_array($children) && $children !== []) {
            $payload['conditions'] = array_values(array_map(
                fn (ConditionInterface $child): ?array => $this->toJson($child),
                $children
            ));
        }

        return $payload;
    }
}
