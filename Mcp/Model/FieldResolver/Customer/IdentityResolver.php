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

class IdentityResolver implements CustomerFieldResolverInterface
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
    public function resolve(CustomerInterface $customer, array $args): array
    {
        unset($args);
        return [
            'id' => (int) $customer->getId(),
            'email' => (string) $customer->getEmail(),
            'firstname' => (string) $customer->getFirstname(),
            'lastname' => (string) $customer->getLastname(),
        ];
    }
}
