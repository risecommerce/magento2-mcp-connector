<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Model\FieldResolver\Order;

use Risecommerce\McpOrderTools\Api\OrderFieldResolverInterface;
use Risecommerce\McpOrderTools\Model\FieldResolver\CastsScalars;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Order lifecycle slice — state, status, is_virtual, hold_before_state.
 */
class StateResolver implements OrderFieldResolverInterface
{
    use CastsScalars;

    public const KEY = 'state';

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 20;
    }

    /** @inheritDoc */
    public function resolve(OrderInterface $order, array $args): array
    {
        return [
            'state' => (string) $order->getState(),
            'status' => (string) $order->getStatus(),
            'is_virtual' => (bool) $order->getIsVirtual(),
            'hold_before_state' => $this->asString($order->getHoldBeforeState()),
            'hold_before_status' => $this->asString($order->getHoldBeforeStatus()),
        ];
    }
}
