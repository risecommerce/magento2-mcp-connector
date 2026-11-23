<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Api;

use Risecommerce\Mcp\Api\FieldResolverInterface;
use Magento\Tax\Api\Data\TaxRateInterface;

interface RateFieldResolverInterface extends FieldResolverInterface
{
    /**
     * @param TaxRateInterface $rate
     * @param array $args
     * @phpstan-param array<string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function resolve(TaxRateInterface $rate, array $args): array;
}
