<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Tool\Catalog\Product;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\CatalogEntityFinder;
use Risecommerce\Mcp\Model\SecureAreaScope;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * MCP write tool `catalog.product.delete`. Non-reversible at the repository
 * level; {@see self::getConfirmationRequired()} prompts the operator.
 */
class ProductDelete implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'catalog.product.delete';
    public const ACL_RESOURCE = 'Risecommerce_McpCatalogTools::tool_catalog_product_delete';

    /**
     * @param CatalogEntityFinder $entityFinder
     * @param ProductRepositoryInterface $productRepository
     * @param SecureAreaScope $secureAreaScope
     */
    public function __construct(
        private readonly CatalogEntityFinder $entityFinder,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SecureAreaScope $secureAreaScope
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
        return 'Delete Product';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Permanently delete a catalog product. Identify by `id` or `sku`.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('id', fn (IntegerBuilder $i) => $i->minimum(1))
            ->string('sku', fn (StringBuilder $s) => $s->minLength(1))
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
        return 'Magento_Catalog::products';
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
        $product = $this->entityFinder->productFrom($arguments);
        $productId = (int) $product->getId();
        $sku = (string) $product->getSku();

        $deleted = $this->secureAreaScope->run(
            fn (): bool => $this->productRepository->delete($product)
        );

        $payload = [
            'deleted' => (bool) $deleted,
            'entity_id' => $productId,
            'sku' => $sku,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode delete result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'entity_id' => $productId,
                'sku' => $sku,
                'deleted' => (bool) $deleted,
            ]
        );
    }
}
