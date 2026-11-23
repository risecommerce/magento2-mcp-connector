<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCmsTools\Tool\Cms\Page;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\McpCmsTools\Api\EntityFinderInterface;
use Risecommerce\McpCmsTools\Api\PageFieldResolverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * `cms.page.get` MCP tool. The response shape is assembled from the
 * DI-injected `$fieldResolvers` array — see `docs/EXTENDING.md`.
 */
class PageGet implements ToolInterface
{
    public const TOOL_NAME = 'cms.page.get';
    public const ACL_RESOURCE = 'Risecommerce_McpCmsTools::tool_cms_page_get';

    /**
     * @param EntityFinderInterface $entityFinder
     * @param ResolverPipeline $pipeline
     * @param PageFieldResolverInterface[] $fieldResolvers
     */
    public function __construct(
        private readonly EntityFinderInterface $entityFinder,
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
        return 'Get CMS Page';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Fetch a single CMS page by numeric id or admin identifier. '
            . 'The response is composed from registered field resolvers — '
            . 'use `fields` to narrow or `exclude` to drop slices (e.g. '
            . '`exclude: ["content"]` to omit the body HTML).';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('id', fn (IntegerBuilder $i) => $i
                ->minimum(1)
                ->description('Numeric cms_page.page_id.')
            )
            ->string('identifier', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('CMS page identifier (URL key), e.g. "home".')
            )
            ->integer('store_id', fn (IntegerBuilder $i) => $i
                ->minimum(0)
                ->description('Store scope for identifier lookup. '
                    . 'Defaults to the current store. Pass 0 for "All Store Views".')
            )
            ->array('fields', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description('Whitelist of resolver keys to include.')
            )
            ->array('exclude', fn (ArrayBuilder $a) => $a
                ->ofStrings()
                ->description('Resolver keys to drop from the default payload.')
            )
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
        $page = $this->entityFinder->pageFrom($arguments);

        $response = [];
        foreach ($this->pipeline->plan($this->fieldResolvers, $arguments) as $resolver) {
            $response[$resolver->getKey()] = $resolver->resolve($page, $arguments);
        }

        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode page payload as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'page_id' => (int) $page->getId(),
                'identifier' => (string) $page->getIdentifier(),
                'is_active' => (bool) $page->isActive(),
            ]
        );
    }
}
