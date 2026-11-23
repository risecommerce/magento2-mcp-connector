<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\JsonRpc\Handler;

use Risecommerce\Mcp\Model\Auth\AuthenticatedContext;
use Risecommerce\Mcp\Model\Auth\WriteAccess;
use Risecommerce\Mcp\Model\Config\ModuleConfig;
use Risecommerce\Mcp\Model\JsonRpc\HandlerInterface;
use Risecommerce\Mcp\Model\JsonRpc\Request;
use Risecommerce\Mcp\Model\JsonRpc\Response;
use Risecommerce\Mcp\Model\Validator\ProtocolVersionValidator;
use stdClass;

/**
 * Handles the `initialize` JSON-RPC method. Advertises the `tools` and
 * `prompts` capabilities; `resources`, `sampling`, `logging` are deliberately
 * omitted. `protocolVersion` echoes the client's requested version when it is
 * supported and falls back to the latest otherwise, as the MCP lifecycle
 * requires; {@see ProtocolVersionValidator} owns the supported list so the
 * header check and this answer never drift.
 */
class InitializeHandler implements HandlerInterface
{
    /**
     * @param ProtocolVersionValidator $protocolVersionValidator
     * @param ModuleConfig $moduleConfig
     * @param string $serverVersion
     */
    public function __construct(
        private readonly ProtocolVersionValidator $protocolVersionValidator,
        private readonly ModuleConfig $moduleConfig,
        private readonly string $serverVersion
    ) {
    }

    /**
     * @inheritDoc
     */
    public function method(): string
    {
        return 'initialize';
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, AuthenticatedContext $context): Response
    {
        $serverInfo = [
            'name' => $this->moduleConfig->getServerName(),
            'version' => $this->serverVersion,
        ];

        $title = $this->moduleConfig->getServerTitle();
        if ($title !== null) {
            $serverInfo['title'] = $title;
        }

        $description = $this->moduleConfig->getServerDescription();
        if ($description !== null) {
            $serverInfo['description'] = $description;
        }

        $websiteUrl = $this->moduleConfig->getServerWebsiteUrl();
        if ($websiteUrl !== null) {
            $serverInfo['websiteUrl'] = $websiteUrl;
        }

        $icon = $this->moduleConfig->getServerIcon();
        if ($icon !== null) {
            $serverInfo['icons'] = [$icon];
        }

        $payload = [
            'protocolVersion' => $this->protocolVersionValidator->negotiate(
                $request->params['protocolVersion'] ?? null
            ),
            'capabilities' => [
                'tools' => new stdClass(),
                'prompts' => ['listChanged' => false],
            ],
            'serverInfo' => $serverInfo,
        ];

        $instructions = $this->moduleConfig->getServerInstructions();
        $writeAccess = WriteAccess::resolve($this->moduleConfig, $context);
        if (!$writeAccess->isAllowed()) {
            $writesOff = 'Write tools are ' . $writeAccess->describeClosedLayer()
                . '. Only read tools are available in this session.';
            $instructions = $instructions === null ? $writesOff : $instructions . "\n\n" . $writesOff;
        }
        if ($instructions !== null) {
            $payload['instructions'] = $instructions;
        }

        return Response::success($request->id, $payload);
    }
}
