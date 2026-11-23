<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCustomerTools\Model\FieldResolver\Customer;

use Risecommerce\McpCustomerTools\Api\CustomerFieldResolverInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class ScopeResolver implements CustomerFieldResolverInterface
{
    public const KEY = 'scope';

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
    public function resolve(CustomerInterface $customer, array $args): array
    {
        unset($args);
        return [
            'website_id' => (int) $customer->getWebsiteId(),
            'store_id' => (int) $customer->getStoreId(),
            'group_id' => (int) $customer->getGroupId(),
            'created_in' => $customer->getCreatedIn(),
        ];
    }
}
