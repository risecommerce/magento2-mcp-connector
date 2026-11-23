<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api\Data;

/**
 * Single message in a {@see \Risecommerce\Mcp\Api\PromptInterface} body.
 *
 * Per MCP spec 2025-06-18 a prompt's `messages[]` wraps each text payload as
 * `{role, content: {type: 'text', text: '…'}}`. We deliberately model only the
 * text variant — image / audio / resource embeds are out of scope for the
 * current iteration.
 */
class PromptMessage
{
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    /**
     * @param string $role Either `user` or `assistant` (per MCP spec).
     * @param string $text The text payload — `{{name}}` placeholders are
     *                     substituted by {@see \Risecommerce\Mcp\Model\Prompt\PromptRenderer}.
     */
    public function __construct(
        public readonly string $role,
        public readonly string $text
    ) {
    }
}
