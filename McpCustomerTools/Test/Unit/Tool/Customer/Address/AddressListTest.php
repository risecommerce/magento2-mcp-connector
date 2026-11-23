<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCustomerTools\Test\Unit\Tool\Customer\Address;

use Risecommerce\Mcp\Model\Util\ResolverPipeline;
use Risecommerce\McpCustomerTools\Model\Search\AddressSearchCriteriaBuilder;
use Risecommerce\McpCustomerTools\Tool\Customer\Address\AddressList;
use Magento\Customer\Api\AddressRepositoryInterface;
use PHPUnit\Framework\TestCase;

class AddressListTest extends TestCase
{
    private AddressList $tool;

    protected function setUp(): void
    {
        $this->tool = new AddressList(
            $this->createMock(AddressRepositoryInterface::class),
            $this->createMock(AddressSearchCriteriaBuilder::class),
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
            'customer_id' => ['integer', 'array'],
            'country_id' => ['string', 'array'],
            'region_id' => ['integer', 'array'],
            'postcode' => ['string', 'array'],
            'city' => 'string',
            'telephone' => 'string',
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
