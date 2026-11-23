<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Tool\Customer\Customer;

use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\Mcp\Model\Search\CustomerSearchCriteriaBuilder;
use Risecommerce\Mcp\Tool\Customer\Customer\CustomerList;
use Magento\Customer\Api\CustomerRepositoryInterface;
use PHPUnit\Framework\TestCase;

class CustomerListTest extends TestCase
{
    private CustomerList $tool;

    protected function setUp(): void
    {
        $this->tool = new CustomerList(
            $this->createMock(CustomerRepositoryInterface::class),
            $this->createMock(CustomerSearchCriteriaBuilder::class),
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
            'email' => ['string', 'array'],
            'firstname' => 'string',
            'lastname' => 'string',
            'group_id' => ['integer', 'array'],
            'store_id' => ['integer', 'array'],
            'website_id' => ['integer', 'array'],
            'store_from_website_id' => ['integer', 'array'],
            'created_at_from' => 'string',
            'created_at_to' => 'string',
            'updated_at_from' => 'string',
            'updated_at_to' => 'string',
            'dob_from' => 'string',
            'dob_to' => 'string',
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
