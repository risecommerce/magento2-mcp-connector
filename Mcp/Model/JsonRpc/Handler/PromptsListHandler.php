<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\JsonRpc\Handler;

use Risecommerce\Mcp\Api\PromptRegistryInterface;
use Risecommerce\Mcp\Model\Auth\AuthenticatedContext;
use Risecommerce\Mcp\Model\Config\ModuleConfig;
use Risecommerce\Mcp\Model\JsonRpc\HandlerInterface;
use Risecommerce\Mcp\Model\JsonRpc\Request;
use Risecommerce\Mcp\Model\JsonRpc\Response;

/**
 * Handles the `prompts/list` JSON-RPC method. Write-requiring prompts are
 * filtered out when either the global allow-writes flag or the token's own
 * `allow_writes` is off, so the client menu never offers an option that
 * `prompts/get` would just reject.
 */
class PromptsListHandler implements HandlerInterface
{
    /**
     * @param PromptRegistryInterface $promptRegistry
     * @param ModuleConfig $config
     */
    public function __construct(
        private readonly PromptRegistryInterface $promptRegistry,
        private readonly ModuleConfig $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function method(): string
    {
        return 'prompts/list';
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, AuthenticatedContext $context): Response
    {
        $writesAllowed = $this->config->isAllowWrites() && $context->token->getAllowWrites();
        $prompts = [];

        foreach ($this->promptRegistry->all() as $prompt) {
            if ($prompt->getRequiresWrite() && !$writesAllowed) {
                continue;
            }
            $arguments = [];
            foreach ($prompt->getArguments() as $argument) {
                $arguments[] = [
                    'name' => $argument->name,
                    'description' => $argument->description,
                    'required' => $argument->required,
                ];
            }
            $prompts[] = [
                'name' => $prompt->getName(),
                'title' => $prompt->getTitle(),
                'description' => $prompt->getDescription(),
                'arguments' => $arguments,
            ];
        }

        return Response::success($request->id, ['prompts' => $prompts]);
    }
}
