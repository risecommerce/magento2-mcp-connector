<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Address;

use Risecommerce\Mcp\Api\AddressFieldResolverInterface;
use Magento\Customer\Api\Data\AddressInterface;

class FlagsResolver implements AddressFieldResolverInterface
{
    public const KEY = 'flags';

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
        return 50;
    }

    /**
     * @inheritDoc
     */
    public function resolve(AddressInterface $address, array $args): array
    {
        unset($args);
        return [
            'default_billing' => (bool) $address->isDefaultBilling(),
            'default_shipping' => (bool) $address->isDefaultShipping(),
        ];
    }
}
