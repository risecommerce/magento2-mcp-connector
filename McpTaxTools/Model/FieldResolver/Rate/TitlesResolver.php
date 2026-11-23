<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Model\FieldResolver\Rate;

use Risecommerce\McpTaxTools\Api\RateFieldResolverInterface;
use Magento\Tax\Api\Data\TaxRateInterface;

/**
 * Per-store-view display labels. Usually empty — `code` is the identifier
 * operators actually recognise.
 */
class TitlesResolver implements RateFieldResolverInterface
{
    public const KEY = 'titles';

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
        return 40;
    }

    /**
     * @inheritDoc
     */
    public function resolve(TaxRateInterface $rate, array $args): array
    {
        unset($args);

        $out = [];
        foreach ($rate->getTitles() ?? [] as $title) {
            $out[] = [
                'store_id' => (int) $title->getStoreId(),
                'value' => (string) $title->getValue(),
            ];
        }
        return $out;
    }
}
