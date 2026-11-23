<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

interface ShipmentFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param ShipmentInterface $shipment
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(ShipmentInterface $shipment, array $args): array;
}
