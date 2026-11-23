<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Test\Unit\Tool\Tax\Rule;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpTaxTools\Tool\Tax\Rule\RuleCreate;
use Risecommerce\McpTaxTools\Tool\Tax\Rule\RuleFieldApplier;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use PHPUnit\Framework\TestCase;

class RuleCreateTest extends TestCase
{
    private RuleCreate $tool;

    protected function setUp(): void
    {
        $this->tool = new RuleCreate(
            $this->createMock(TaxRuleRepositoryInterface::class),
            $this->createMock(TaxRuleInterfaceFactory::class),
            new RuleFieldApplier()
        );
    }

    public function testCreateIsWriteGatedAndDoesNotRequireConfirmation(): void
    {
        $this->assertSame(WriteMode::WRITE, $this->tool->getWriteMode());
        $this->assertFalse($this->tool->getConfirmationRequired());
    }

    public function testToolIdentity(): void
    {
        $this->assertSame('tax.rule.create', $this->tool->getName());
        $this->assertSame('Risecommerce_McpTaxTools::tool_tax_rule_create', $this->tool->getAclResource());
        $this->assertSame('Magento_Tax::manage_tax', $this->tool->getUnderlyingAclResource());
    }
}
