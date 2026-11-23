<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Tool;

use InvalidArgumentException;
use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolRegistryInterface;
use Risecommerce\Mcp\Model\Util\RegisteredEntries;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Validates tool names match the canonical dotted regex and that each di.xml
 * key matches the tool's own getName() — catches drift between registration
 * and self-reported identity.
 */
class ToolRegistry implements ToolRegistryInterface
{
    /** @var array<string, ToolInterface> */
    private array $tools;

    /**
     * Wire-form (dot→underscore) name → canonical name. Built once at
     * construction so the dispatcher can accept the underscored names that
     * clients with stricter validators (notably Claude.ai's frontend, which
     * enforces `^[a-zA-Z0-9_-]{1,64}$`) emit on tools/call.
     *
     * @var array<string, string>
     */
    private array $wireToCanonical;

    /**
     * @param array<string, ToolInterface> $tools
     * @return void
     */
    public function __construct(array $tools = [])
    {
        RegisteredEntries::assertValid($tools, [ToolInterface::class], 'MCP tool');
        $this->tools = $tools;
        $this->wireToCanonical = $this->buildWireMap($tools);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ToolInterface
    {
        if (!isset($this->tools[$name])) {
            throw NoSuchEntityException::singleField('name', $name);
        }
        return $this->tools[$name];
    }

    /**
     * @inheritDoc
     */
    public function getCanonicalName(string $name): ?string
    {
        if (isset($this->tools[$name])) {
            return $name;
        }
        return $this->wireToCanonical[$name] ?? null;
    }

    /**
     * Maps the dot→underscore wire form back to the canonical name. Throws on
     * collisions (two distinct canonicals projecting to the same wire form),
     * which would otherwise let `tools/call` route nondeterministically.
     *
     * @param array<string, ToolInterface> $tools
     * @return array<string, string>
     */
    private function buildWireMap(array $tools): array
    {
        $map = [];
        foreach ($tools as $name => $tool) {
            $wire = str_replace('.', '_', $name);
            if ($wire === $name) {
                continue;
            }
            if (isset($map[$wire])) {
                throw new InvalidArgumentException(sprintf(
                    'MCP tool wire-format collision: "%s" and "%s" both project to "%s".',
                    $map[$wire],
                    $name,
                    $wire
                ));
            }
            $map[$wire] = $name;
        }
        return $map;
    }

}
