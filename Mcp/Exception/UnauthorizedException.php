<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Exception;

use RuntimeException;

/**
 * Bearer verification failure; mapped to HTTP 401 + JSON-RPC UNAUTHORIZED.
 */
class UnauthorizedException extends RuntimeException
{
}
