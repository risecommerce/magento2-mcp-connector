<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Tool\Inventory\Source;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\SourcePayload;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * MCP read tool `inventory.source.get`.
 */
class SourceGet implements ToolInterface
{
    public const TOOL_NAME = 'inventory.source.get';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_source_get';

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourcePayload $sourcePayload
     */
    public function __construct(
        private readonly SourceRepositoryInterface $sourceRepository,
        private readonly SourcePayload $sourcePayload
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
        return 'Get Inventory Source';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch a single inventory source by its `source_code`, including '
            . 'its address and contact details.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('source_code', fn (StringBuilder $s) => $s
                ->minLength(1)->maxLength(255)
                ->description('Unique source code, e.g. default or warehouse_riga.')
                ->required())
            ->toArray();
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
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $sourceCode = isset($arguments['source_code']) && is_string($arguments['source_code'])
            ? $arguments['source_code']
            : '';
        if ($sourceCode === '') {
            throw new LocalizedException(__('"source_code" is required.'));
        }

        $source = $this->sourceRepository->get($sourceCode);
        $payload = $this->sourcePayload->toArray($source);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode source as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['source_code' => $sourceCode]
        );
    }
}
