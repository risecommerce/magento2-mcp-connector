<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCmsTools\Test\Unit\Tool\Cms\Block;

use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\McpCmsTools\Model\Search\BlockSearchCriteriaBuilder;
use Risecommerce\McpCmsTools\Tool\Cms\Block\BlockList;
use Magento\Cms\Api\BlockRepositoryInterface;
use PHPUnit\Framework\TestCase;

class BlockListTest extends TestCase
{
    private BlockList $tool;

    protected function setUp(): void
    {
        $this->tool = new BlockList(
            $this->createMock(BlockRepositoryInterface::class),
            $this->createMock(BlockSearchCriteriaBuilder::class),
            $this->createMock(ResolverPipeline::class),
            []
        );
    }

    public function testSchemaDeclaresTypedFilterKeys(): void
    {
        $schema = $this->tool->getInputSchema();
        $this->assertIsArray($schema['properties']);
        $filters = $schema['properties']['filters'];
        $this->assertIsArray($filters);
        $this->assertTrue($filters['additionalProperties']);
        $props = $filters['properties'];
        $this->assertIsArray($props);

        $expected = [
            'identifier' => ['string', 'array'],
            'title' => 'string',
            'is_active' => 'boolean',
            'store_id' => ['integer', 'array'],
            'website_id' => ['integer', 'array'],
            'created_at_from' => 'string',
            'created_at_to' => 'string',
            'update_time_from' => 'string',
            'update_time_to' => 'string',
        ];
        $this->assertSame(array_keys($expected), array_keys($props));
        foreach ($expected as $key => $type) {
            $prop = $props[$key];
            $this->assertIsArray($prop);
            $this->assertSame($type, $prop['type'], $key);
            $this->assertNotEmpty($prop['description'], $key);
        }
    }
}
