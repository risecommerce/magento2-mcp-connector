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

class TimestampsResolver implements CustomerFieldResolverInterface
{
    public const KEY = 'timestamps';

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
        return 90;
    }

    /**
     * @inheritDoc
     */
    public function resolve(CustomerInterface $customer, array $args): array
    {
        unset($args);
        return [
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
        ];
    }
}
