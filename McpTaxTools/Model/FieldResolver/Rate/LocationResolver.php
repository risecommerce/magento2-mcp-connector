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

class LocationResolver implements RateFieldResolverInterface
{
    public const KEY = 'location';

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
    public function resolve(TaxRateInterface $rate, array $args): array
    {
        unset($args);

        $regionId = $rate->getTaxRegionId();
        $zipFrom = $rate->getZipFrom();
        $zipTo = $rate->getZipTo();

        return [
            'country_id' => (string) $rate->getTaxCountryId(),
            'region_id' => $regionId !== null ? (int) $regionId : null,
            'region_name' => $rate->getRegionName() !== null ? (string) $rate->getRegionName() : null,
            'postcode' => $rate->getTaxPostcode() !== null ? (string) $rate->getTaxPostcode() : null,
            'zip_is_range' => (bool) $rate->getZipIsRange(),
            'zip_from' => $zipFrom !== null ? (int) $zipFrom : null,
            'zip_to' => $zipTo !== null ? (int) $zipTo : null,
        ];
    }
}
