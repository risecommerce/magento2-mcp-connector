<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Logger;

use Risecommerce\Mcp\Api\LoggerInterface;
use Monolog\Logger as MonologLogger;

/**
 * Dedicated Monolog channel for the MCP module; see {@see LoggerInterface}.
 */
class Logger extends MonologLogger implements LoggerInterface
{
}
