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
 * Handles the `ping` JSON-RPC method — required by MCP spec as a liveness check.
 */
class PingHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     */
    public function method(): string
    {
        return 'ping';
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, AuthenticatedContext $context): Response
    {
        unset($context);
        return Response::success($request->id, []);
    }
}
