<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

/**
 * Marker contract for DI-injected field resolvers contributing named slices of a tool response.
 */
interface FieldResolverInterface
{
    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * Lower values render earlier; built-ins default to 100.
     *
     * @return int
     */
    public function getSortOrder(): int;
}
