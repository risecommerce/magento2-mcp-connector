<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Customer\Api\Data\AddressInterface;

interface AddressFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param AddressInterface $address
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(AddressInterface $address, array $args): array;
}
