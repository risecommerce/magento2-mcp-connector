<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;

interface InvoiceFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param InvoiceInterface $invoice
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(InvoiceInterface $invoice, array $args): array;
}
