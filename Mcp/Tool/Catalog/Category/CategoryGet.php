<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Catalog\Category;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\Mcp\Api\CategoryFieldResolverInterface;
use Risecommerce\Mcp\Model\CatalogEntityFinder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

/**
 * MCP tool `catalog.category.get` — fetch one category by id.
 */
class CategoryGet implements ToolInterface
{
    public const TOOL_NAME = 'catalog.category.get';
    public const ACL_RESOURCE = 'Risecommerce_McpCatalogTools::tool_catalog_category_get';

    /**
     * @param CatalogEntityFinder $entityFinder
     * @param ResolverPipeline $pipeline
     * @param CategoryFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly CatalogEntityFinder $entityFinder,
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
        return 'Get Category';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch a single catalog category by numeric `id` (or its '
            . 'alias `category_id`). The response is composed from '
            . 'registered field resolvers — use `fields` to '
            . 'narrow (e.g. `fields: ["identity", "tree"]`) or `exclude` to '
            . 'drop slices. Note: `products` returns every product id '
            . 'assigned to the category, which may be sizeable for broad '
            . 'roots — pass `exclude: ["products"]` to drop it.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Numeric catalog_category_entity.entity_id.'))
            ->integer('category_id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Alias of `id`.'))
            ->integer('store_id', fn (IntegerBuilder $i) => $i
                ->minimum(0)
                ->description('Store scope for store-scoped attributes. '
                    . 'Defaults to admin scope.'))
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
        $category = $this->entityFinder->categoryFrom($arguments, Store::DEFAULT_STORE_ID);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($category, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode category payload as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'category_id' => (int) $category->getId(),
                'name' => (string) $category->getName(),
                'level' => $category->getLevel() !== null ? (int) $category->getLevel() : null,
            ]
        );
    }
}
