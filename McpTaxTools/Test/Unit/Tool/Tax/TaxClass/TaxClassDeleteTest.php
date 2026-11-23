<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Test\Unit\Tool\Tax\TaxClass;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpTaxTools\Tool\Tax\TaxClass\TaxClassDelete;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaxClassDeleteTest extends TestCase
{
    /** @var TaxClassRepositoryInterface&MockObject */
    private TaxClassRepositoryInterface $taxClassRepository;

    private TaxClassDelete $tool;

    protected function setUp(): void
    {
        $this->taxClassRepository = $this->createMock(TaxClassRepositoryInterface::class);
        $this->tool = new TaxClassDelete($this->taxClassRepository);
    }

    public function testDeleteIsWriteGatedAndRequiresConfirmation(): void
    {
        $this->assertSame(WriteMode::WRITE, $this->tool->getWriteMode());
        $this->assertTrue($this->tool->getConfirmationRequired());
    }

    public function testToolIdentity(): void
    {
        $this->assertSame('tax.class.delete', $this->tool->getName());
        $this->assertSame('Risecommerce_McpTaxTools::tool_tax_class_delete', $this->tool->getAclResource());
        $this->assertSame('Magento_Tax::manage_tax', $this->tool->getUnderlyingAclResource());
    }

    public function testExecuteCapturesClassSnapshotBeforeDeleting(): void
    {
        $taxClass = $this->createMock(TaxClassInterface::class);
        $taxClass->method('getClassName')->willReturn('Taxable Goods');
        $taxClass->method('getClassType')->willReturn('PRODUCT');

        $callOrder = [];

        $this->taxClassRepository->expects($this->once())
            ->method('get')
            ->with(2)
            ->willReturnCallback(function () use (&$callOrder, $taxClass) {
                $callOrder[] = 'get';
                return $taxClass;
            });
        $this->taxClassRepository->expects($this->once())
            ->method('deleteById')
            ->with(2)
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'deleteById';
                return true;
            });

        $result = $this->tool->execute(['class_id' => 2]);

        $this->assertSame(['get', 'deleteById'], $callOrder);
        $this->assertSame([
            'id' => 2,
            'name' => 'Taxable Goods',
            'type' => 'PRODUCT',
        ], $result->getAuditSummary());
    }
}
