<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Test\Unit\Tool\Tax\Rule;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpTaxTools\Tool\Tax\Rule\RuleFieldApplier;
use Risecommerce\McpTaxTools\Tool\Tax\Rule\RuleUpdate;
use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleUpdateTest extends TestCase
{
    /** @var TaxRuleRepositoryInterface&MockObject */
    private TaxRuleRepositoryInterface $ruleRepository;

    private RuleUpdate $tool;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->createMock(TaxRuleRepositoryInterface::class);
        $this->tool = new RuleUpdate($this->ruleRepository, new RuleFieldApplier());
    }

    public function testUpdateIsWriteGatedAndDoesNotRequireConfirmation(): void
    {
        $this->assertSame(WriteMode::WRITE, $this->tool->getWriteMode());
        $this->assertFalse($this->tool->getConfirmationRequired());
    }

    public function testToolIdentity(): void
    {
        $this->assertSame('tax.rule.update', $this->tool->getName());
        $this->assertSame('Risecommerce_McpTaxTools::tool_tax_rule_update', $this->tool->getAclResource());
        $this->assertSame('Magento_Tax::manage_tax', $this->tool->getUnderlyingAclResource());
    }

    public function testDescriptionWarnsThatLinkedIdsAreReplacedNotMerged(): void
    {
        $this->assertStringContainsString('replaced', $this->tool->getDescription());
        $this->assertStringContainsString('not merged', $this->tool->getDescription());
    }

    public function testAuditSummaryRecordsTheLinkSetBeforeReplacingIt(): void
    {
        $rule = $this->createMock(TaxRuleInterface::class);
        $rule->method('getId')->willReturn(7);
        $rule->method('getCode')->willReturn('Rule1');
        $rule->method('getTaxRateIds')->willReturn([1, 2]);
        $rule->method('getCustomerTaxClassIds')->willReturn([3]);
        $rule->method('getProductTaxClassIds')->willReturn([2]);

        $this->ruleRepository->method('get')->with(7)->willReturn($rule);
        $this->ruleRepository->method('save')->willReturnArgument(0);

        $result = $this->tool->execute(['rule_id' => 7, 'tax_rate_ids' => [3]]);

        $this->assertSame([
            'code' => 'Rule1',
            'tax_rate_ids' => [1, 2],
            'customer_tax_class_ids' => [3],
            'product_tax_class_ids' => [2],
        ], $result->getAuditSummary()['before']);
    }
}
