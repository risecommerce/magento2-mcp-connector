<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\JsonRpc;

use Risecommerce\Mcp\Model\Auth\AuthenticatedContext;

/**
 * Contract every JSON-RPC method handler implements. The {@see Dispatcher}
 * indexes handlers by their own `method()` return value — the di.xml array
 * key is informational only.
 */
interface HandlerInterface
{
    /**
     * JSON-RPC method name this handler responds to (e.g. "tools/list").
     *
     * @return string
     */
    public function method(): string;

    /**
     * Return null ONLY for notification methods (no client `id`, no response).
     * Requests with an id MUST return a Response.
     *
     * @param Request $request
     * @param AuthenticatedContext $context
     * @return Response|null
     */
    public function handle(Request $request, AuthenticatedContext $context): ?Response;
}
