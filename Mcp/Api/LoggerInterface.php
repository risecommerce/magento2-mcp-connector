<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Marker logger routing module log calls into `var/log/risecommerce_mcp.log` via DI preference.
 */
interface LoggerInterface extends PsrLoggerInterface
{
}
