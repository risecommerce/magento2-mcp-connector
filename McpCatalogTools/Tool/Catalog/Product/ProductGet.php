<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCatalogTools\Tool\Catalog\Product;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\McpCatalogTools\Api\ProductFieldResolverInterface;
use Risecommerce\McpCatalogTools\Model\EntityFinder;
use Magento\Framework\Exception\LocalizedException;

/**
 * MCP tool `catalog.product.get` — fetch one product by id or sku.
 */
class ProductGet implements ToolInterface
{
    public const TOOL_NAME = 'catalog.product.get';
    public const ACL_RESOURCE = 'Risecommerce_McpCatalogTools::tool_catalog_product_get';

    /**
     * @param EntityFinder $entityFinder
     * @param ResolverPipeline $pipeline
     * @param ProductFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly EntityFinder $entityFinder,
        private readonly ResolverPipeline $pipeline,
        private readonly array $fieldResolvers = []
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
        return 'Get Product';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch a single catalog product by numeric id or SKU. The '
            . 'response is composed from registered field resolvers — use '
            . '`fields` to narrow or `exclude` to drop slices (e.g. '
            . '`exclude: ["media", "custom_attributes"]` for a leaner payload). '
            . 'The `type_options` slice describes configurable-product '
            . 'configuration attributes (and variant entity ids) and '
            . 'bundle-product options (title, type, required flag, linked '
            . 'SKUs); use it to discover variants of a configurable or the '
            . 'item structure of a bundle. The `variants` slice resolves a '
            . 'configurable\'s children to their sku, name, price, '
            . 'special_price and status (null for non-configurables) — a '
            . 'configurable parent itself carries no meaningful price, so '
            . 'read real prices from there.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Numeric catalog_product_entity.entity_id.'))
            ->string('sku', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Product SKU (the operator-facing identifier).'))
            ->array('fields', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description('Whitelist of resolver keys to include.'))
            ->array('exclude', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description('Resolver keys to drop from the default payload.'))
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
        $product = $this->entityFinder->productFrom($arguments);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($product, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode product payload as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'product_id' => (int) $product->getId(),
                'sku' => (string) $product->getSku(),
                'type_id' => (string) $product->getTypeId(),
            ]
        );
    }
}
