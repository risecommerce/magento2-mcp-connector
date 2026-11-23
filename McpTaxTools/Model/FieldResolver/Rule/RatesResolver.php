<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Model\FieldResolver\Rule;

use Risecommerce\McpTaxTools\Api\RuleFieldResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;

/**
 * Expands the rule's rate ids into full rate descriptors, so one rule read
 * shows which destinations and percentages the rule actually applies.
 */
class RatesResolver implements RuleFieldResolverInterface
{
    public const KEY = 'rates';

    /**
     * @param TaxRateRepositoryInterface $rateRepository
     */
    public function __construct(
        private readonly TaxRateRepositoryInterface $rateRepository
    ) {
    }

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
    public function resolve(TaxRuleInterface $rule, array $args): array
    {
        unset($args);

        $out = [];
        foreach ($rule->getTaxRateIds() ?? [] as $rateId) {
            if (!is_numeric($rateId)) {
                continue;
            }
            $id = (int) $rateId;

            try {
                $rate = $this->rateRepository->get($id);
            } catch (NoSuchEntityException) {
                $out[] = ['id' => $id, 'missing' => true];
                continue;
            }

            $out[] = [
                'id' => $id,
                'code' => (string) $rate->getCode(),
                'country_id' => (string) $rate->getTaxCountryId(),
                'region_id' => $rate->getTaxRegionId() !== null ? (int) $rate->getTaxRegionId() : null,
                'postcode' => $rate->getTaxPostcode() !== null ? (string) $rate->getTaxPostcode() : null,
                'percent' => (float) $rate->getRate(),
            ];
        }
        return $out;
    }
}
