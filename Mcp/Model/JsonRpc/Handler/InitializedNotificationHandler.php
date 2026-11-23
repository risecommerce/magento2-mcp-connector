<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\JsonRpc\Handler;

use Risecommerce\Mcp\Model\Auth\AuthenticatedContext;
use Risecommerce\Mcp\Model\JsonRpc\HandlerInterface;
use Risecommerce\Mcp\Model\JsonRpc\Request;
use Risecommerce\Mcp\Model\JsonRpc\Response;

/**
 * Handles the `notifications/initialized` notification sent after a successful
 * `initialize` handshake. Notifications expect no response — this handler
 * always returns null, which the transport translates into a 202 with no body.
 */
class InitializedNotificationHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     */
    public function method(): string
    {
        return 'notifications/initialized';
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, AuthenticatedContext $context): ?Response
    {
        unset($request, $context);
        return null;
    }
}
