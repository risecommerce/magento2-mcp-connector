<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\System;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Auth\AuthenticatedContextProvider;
use Risecommerce\Mcp\Model\Auth\WriteAccess;
use Risecommerce\Mcp\Model\Config\ModuleConfig;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Magento\Framework\Exception\LocalizedException;

/**
 * MCP tool `system.whoami` — resolve "me" to the administrator and token
 * authenticated for the current request.
 */
class WhoAmI implements ToolInterface
{
    public const TOOL_NAME = 'system.whoami';
    public const ACL_RESOURCE = 'Risecommerce_Mcp::tool_system_whoami';

    /**
     * @param AuthenticatedContextProvider $contextProvider
     * @param ModuleConfig $config
     */
    public function __construct(
        private readonly AuthenticatedContextProvider $contextProvider,
        private readonly ModuleConfig $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Who Am I';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Return the Magento administrator and MCP connection authenticated '
            . 'for this request, including whether write tools are currently allowed '
            . '(allow_writes) and which layer allows them: the store setting '
            . '(store_allow_writes) and the token (token_allow_writes). '
            . 'Call this first whenever a request says "me", "my" or "mine" (e.g. '
            . '"my orders", "tickets assigned to me") so you can substitute the real '
            . 'admin identity instead of guessing.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    /**
     * @inheritDoc
     */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::READ;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationRequired(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $context = $this->contextProvider->get();
        $adminUserId = $context->getAdminUserId();
        $writeAccess = WriteAccess::resolve($this->config, $context);
        $allowWrites = $writeAccess->isAllowed();
        $payload = [
            'admin_user_id' => $adminUserId,
            'username' => $this->stringOrEmpty($context->adminUser->getUsername()),
            'email' => $this->stringOrEmpty($context->adminUser->getEmail()),
            'firstname' => $this->stringOrEmpty($context->adminUser->getFirstName()),
            'lastname' => $this->stringOrEmpty($context->adminUser->getLastName()),
            'token_name' => $context->token->getName(),
            'allow_writes' => $allowWrites,
            'store_allow_writes' => $writeAccess->storeAllowsWrites,
            'token_allow_writes' => $writeAccess->tokenAllowsWrites,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode authenticated context as JSON.'));
        }

        return ToolResult::text($json, [
            'admin_user_id' => $adminUserId,
            'allow_writes' => $allowWrites,
        ]);
    }

    /**
     * Admin user getters are untyped AbstractModel reads — normalize to string.
     *
     * @param mixed $value
     * @return string
     */
    private function stringOrEmpty(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
