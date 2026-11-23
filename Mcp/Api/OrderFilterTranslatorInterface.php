<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Extension point for the `sales.order.list` filter argument. Translators are
 * consulted in DI order; the first to return true from `supports()` wins.
 * Unrecognised keys fail with `INVALID_PARAMS`.
 */
interface OrderFilterTranslatorInterface
{
    /**
     * @param string $key
     * @return bool
     */
    public function supports(string $key): bool;

    /**
     * @param string $key
     * @param mixed $value
     * @param SearchCriteriaBuilder $builder
     * @return void
     */
    public function translate(string $key, mixed $value, SearchCriteriaBuilder $builder): void;
}
