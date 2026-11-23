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
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\BooleanBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\NumberBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\SourcePayload;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * MCP write tool `inventory.source.update`. PATCH-style.
 *
 * MSI has no delete for sources — retiring one means `enabled: false`, which is
 * what the admin UI does too.
 */
class SourceUpdate implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'inventory.source.update';
    public const ACL_RESOURCE = 'Risecommerce_McpInventoryTools::tool_inventory_source_update';

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
        return 'Update Inventory Source';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Update an inventory source by `source_code`. Only the fields you '
            . 'pass are changed. Sources cannot be deleted in Magento — retire one '
            . 'by passing `enabled: false`, which stops it contributing stock to '
            . 'any website.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('source_code', fn (StringBuilder $s) => $s
                ->minLength(1)->maxLength(255)
                ->description('Source to update.')
                ->required())
            ->string('name', fn (StringBuilder $s) => $s->minLength(1)->maxLength(255))
            ->boolean('enabled', fn (BooleanBuilder $b) => $b
                ->description('Set false to retire the source.'))
            ->string('description', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('country_id', fn (StringBuilder $s) => $s->minLength(2)->maxLength(2))
            ->integer('region_id', fn (IntegerBuilder $i) => $i->minimum(1))
            ->string('region', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('city', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('street', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('postcode', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('contact_name', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('email', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('phone', fn (StringBuilder $s) => $s->maxLength(255))
            ->string('fax', fn (StringBuilder $s) => $s->maxLength(255))
            ->number('latitude', fn (NumberBuilder $n) => $n->minimum(-90)->maximum(90))
            ->number('longitude', fn (NumberBuilder $n) => $n->minimum(-180)->maximum(180))
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
    public function getUnderlyingAclResource(): ?string
    {
        return 'Magento_InventoryApi::source_edit';
    }

    /**
     * @inheritDoc
     */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::WRITE;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationRequired(): bool
    {
        return true;
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
        $this->sourcePayload->apply($source, $arguments);

        $this->sourceRepository->save($source);

        $payload = $this->sourcePayload->toArray($this->sourceRepository->get($sourceCode));

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode updated source as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: ['source_code' => $sourceCode]
        );
    }
}
