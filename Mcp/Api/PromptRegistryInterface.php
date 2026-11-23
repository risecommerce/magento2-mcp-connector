<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Registry of all MCP prompts, populated via DI array injection at compile
 * time. Mirrors {@see ToolRegistryInterface} — same compile-time validation,
 * same extension story for satellite modules.
 */
interface PromptRegistryInterface
{
    /**
     * @return array<string, PromptInterface>
     */
    public function all(): array;

    /**
     * @param string $name
     */
    public function has(string $name): bool;

    /**
     * @param string $name
     * @throws NoSuchEntityException When the prompt is not registered.
     */
    public function get(string $name): PromptInterface;
}
