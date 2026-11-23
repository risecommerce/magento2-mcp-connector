<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Test\Unit\Tool\Tax\TaxClass;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpTaxTools\Tool\Tax\TaxClass\TaxClassCreate;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use PHPUnit\Framework\TestCase;

class TaxClassCreateTest extends TestCase
{
    private TaxClassCreate $tool;

    protected function setUp(): void
    {
        $this->tool = new TaxClassCreate(
            $this->createMock(TaxClassRepositoryInterface::class),
            $this->createMock(TaxClassInterfaceFactory::class)
        );
    }

    public function testToolIdentityAndGating(): void
    {
        $this->assertSame('tax.class.create', $this->tool->getName());
        $this->assertSame('Risecommerce_McpTaxTools::tool_tax_class_create', $this->tool->getAclResource());
        $this->assertSame(WriteMode::WRITE, $this->tool->getWriteMode());
        $this->assertFalse($this->tool->getConfirmationRequired());
        $this->assertSame('Magento_Tax::manage_tax', $this->tool->getUnderlyingAclResource());
    }

    public function testSchemaConstrainsClassTypeToTheTwoValidValues(): void
    {
        $schema = $this->tool->getInputSchema();
        $this->assertIsArray($schema['properties']);
        $classType = $schema['properties']['class_type'];
        $this->assertIsArray($classType);
        $this->assertSame(['PRODUCT', 'CUSTOMER'], $classType['enum']);
        $this->assertIsArray($schema['required']);
        $this->assertContains('class_name', $schema['required']);
        $this->assertContains('class_type', $schema['required']);
    }
}
