<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\RateLimiter;

use Risecommerce\Mcp\Api\RateLimiterInterface;

/**
 * Default rate limiter — never throttles. Override via DI preference in etc/di.xml.
 */
class NoOpRateLimiter implements RateLimiterInterface
{
    /**
     * @inheritDoc
     */
    public function check(int $adminUserId, string $toolName): void
    {
        unset($adminUserId, $toolName);
    }
}
