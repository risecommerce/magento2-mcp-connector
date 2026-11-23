<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

/**
 * Routes MCP log events into `var/log/risecommerce_mcp.log`.
 */
class Handler extends Base
{
    /** @var string */
    protected $fileName = '/var/log/risecommerce_mcp.log';

    /** @var int */
    protected $loggerType = MonologLogger::DEBUG;
}
