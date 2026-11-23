<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Order;

use Risecommerce\Mcp\Api\OrderFieldResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Marks a resolver slice as opt-in: the wrapped resolver runs only when its
 * key is explicitly requested via `fields`. Keeps expensive slices (e.g. line
 * items) out of default list payloads without denying bulk access.
 */
class OnDemandResolver implements OrderFieldResolverInterface
{
    /**
     * @param OrderFieldResolverInterface $inner
     */
    public function __construct(
        private readonly OrderFieldResolverInterface $inner
    ) {
    }

    /** @inheritDoc */
    public function getKey(): string
    {
        return $this->inner->getKey();
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return $this->inner->getSortOrder();
    }

    /** @inheritDoc */
    public function resolve(OrderInterface $order, array $args): array
    {
        return $this->inner->resolve($order, $args);
    }

    /**
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return bool
     */
    public function isRequested(array $args): bool
    {
        $fields = $args['fields'] ?? null;
        if (is_string($fields) && $fields !== '') {
            $fields = [$fields];
        }
        if (!is_array($fields)) {
            return false;
        }

        return in_array($this->inner->getKey(), $fields, true);
    }
}
