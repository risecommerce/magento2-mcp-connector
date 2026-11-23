<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;

/** Block-side extension point for the `cms.block.list` filter argument. */
interface BlockFilterTranslatorInterface
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
