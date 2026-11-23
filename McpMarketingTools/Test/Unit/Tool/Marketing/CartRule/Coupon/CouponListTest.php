<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpMarketingTools\Test\Unit\Tool\Marketing\CartRule\Coupon;

use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\McpMarketingTools\Model\Search\CouponSearchBuilder;
use Risecommerce\McpMarketingTools\Tool\Marketing\CartRule\Coupon\CouponList;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use PHPUnit\Framework\TestCase;

class CouponListTest extends TestCase
{
    private CouponList $tool;

    protected function setUp(): void
    {
        $this->tool = new CouponList(
            $this->createMock(CouponRepositoryInterface::class),
            $this->createMock(CouponSearchBuilder::class),
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
            'rule_id' => ['integer', 'array'],
            'code' => 'string',
            'type' => 'string',
            'is_primary' => 'boolean',
            'created_after' => 'string',
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
