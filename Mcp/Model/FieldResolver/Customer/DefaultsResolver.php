<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Customer;

use Risecommerce\Mcp\Api\CustomerFieldResolverInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class DefaultsResolver implements CustomerFieldResolverInterface
{
    public const KEY = 'defaults';

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
    public function resolve(CustomerInterface $customer, array $args): array
    {
        unset($args);
        return [
            'default_billing' => $customer->getDefaultBilling(),
            'default_shipping' => $customer->getDefaultShipping(),
        ];
    }
}
