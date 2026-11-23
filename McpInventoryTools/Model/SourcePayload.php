<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model;

use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Shared projection of an MSI source into the tool response shape.
 */
class SourcePayload
{
    /**
     * @param SourceInterface $source
     * @return array<string, mixed>
     */
    public function toArray(SourceInterface $source): array
    {
        return [
            'source_code' => (string) $source->getSourceCode(),
            'name' => (string) $source->getName(),
            'enabled' => (bool) $source->isEnabled(),
            'description' => $source->getDescription(),
            'country_id' => $source->getCountryId(),
            'region_id' => $source->getRegionId() !== null ? (int) $source->getRegionId() : null,
            'region' => $source->getRegion(),
            'city' => $source->getCity(),
            'street' => $source->getStreet(),
            'postcode' => $source->getPostcode(),
            'contact_name' => $source->getContactName(),
            'email' => $source->getEmail(),
            'phone' => $source->getPhone(),
            'fax' => $source->getFax(),
            'latitude' => $source->getLatitude() !== null ? (float) $source->getLatitude() : null,
            'longitude' => $source->getLongitude() !== null ? (float) $source->getLongitude() : null,
        ];
    }

    /**
     * Apply only the keys present in the arguments, so updates stay PATCH-style.
     *
     * @param SourceInterface $source
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @return void
     */
    public function apply(SourceInterface $source, array $arguments): void
    {
        $strings = [
            'name' => 'setName',
            'description' => 'setDescription',
            'country_id' => 'setCountryId',
            'region' => 'setRegion',
            'city' => 'setCity',
            'street' => 'setStreet',
            'postcode' => 'setPostcode',
            'contact_name' => 'setContactName',
            'email' => 'setEmail',
            'phone' => 'setPhone',
            'fax' => 'setFax',
        ];

        foreach ($strings as $key => $setter) {
            if (array_key_exists($key, $arguments) && is_scalar($arguments[$key])) {
                $source->{$setter}((string) $arguments[$key]);
            }
        }

        if (array_key_exists('enabled', $arguments)) {
            $source->setEnabled((bool) $arguments['enabled']);
        }
        if (isset($arguments['region_id']) && is_numeric($arguments['region_id'])) {
            $source->setRegionId((int) $arguments['region_id']);
        }
        if (isset($arguments['latitude']) && is_numeric($arguments['latitude'])) {
            $source->setLatitude((float) $arguments['latitude']);
        }
        if (isset($arguments['longitude']) && is_numeric($arguments['longitude'])) {
            $source->setLongitude((float) $arguments['longitude']);
        }
    }
}
