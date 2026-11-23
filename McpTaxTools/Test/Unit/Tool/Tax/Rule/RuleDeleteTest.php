<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Test\Unit\Tool\Tax\Rule;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpTaxTools\Tool\Tax\Rule\RuleDelete;
use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleDeleteTest extends TestCase
{
    /** @var TaxRuleRepositoryInterface&MockObject */
    private TaxRuleRepositoryInterface $ruleRepository;

    private RuleDelete $tool;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->createMock(TaxRuleRepositoryInterface::class);
        $this->tool = new RuleDelete($this->ruleRepository);
    }

    public function testDeleteIsWriteGatedAndRequiresConfirmation(): void
    {
        $this->assertSame(WriteMode::WRITE, $this->tool->getWriteMode());
        $this->assertTrue($this->tool->getConfirmationRequired());
    }

    public function testToolIdentity(): void
    {
        $this->assertSame('tax.rule.delete', $this->tool->getName());
        $this->assertSame('Risecommerce_McpTaxTools::tool_tax_rule_delete', $this->tool->getAclResource());
        $this->assertSame('Magento_Tax::manage_tax', $this->tool->getUnderlyingAclResource());
    }

    public function testDescriptionWarnsThatDeletionIsIrreversible(): void
    {
        $this->assertStringContainsString('cannot be undone', $this->tool->getDescription());
    }

    public function testExecuteCapturesLinkSnapshotBeforeDeleting(): void
    {
        $rule = $this->createMock(TaxRuleInterface::class);
        $rule->method('getId')->willReturn(7);
        $rule->method('getCode')->willReturn('Rule1');
        $rule->method('getTaxRateIds')->willReturn([1, 2]);
        $rule->method('getCustomerTaxClassIds')->willReturn([3]);
        $rule->method('getProductTaxClassIds')->willReturn([2]);

        $callOrder = [];

        $this->ruleRepository->expects($this->once())
            ->method('get')
            ->with(7)
            ->willReturnCallback(function () use (&$callOrder, $rule) {
                $callOrder[] = 'get';
                return $rule;
            });
        $this->ruleRepository->expects($this->once())
            ->method('deleteById')
            ->with(7)
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'deleteById';
                return true;
            });

        $result = $this->tool->execute(['rule_id' => 7]);

        $this->assertSame(['get', 'deleteById'], $callOrder);
        $this->assertSame([
            'id' => 7,
            'code' => 'Rule1',
            'tax_rate_ids' => [1, 2],
            'customer_tax_class_ids' => [3],
            'product_tax_class_ids' => [2],
        ], $result->getAuditSummary());
    }
}
