<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Test\Unit\Tool\Tax\Rate;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpTaxTools\Tool\Tax\Rate\RateFieldApplier;
use Risecommerce\McpTaxTools\Tool\Tax\Rate\RateUpdate;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateUpdateTest extends TestCase
{
    /** @var TaxRateRepositoryInterface&MockObject */
    private TaxRateRepositoryInterface $rateRepository;

    private RateUpdate $tool;

    protected function setUp(): void
    {
        $this->rateRepository = $this->createMock(TaxRateRepositoryInterface::class);
        $this->tool = new RateUpdate($this->rateRepository, new RateFieldApplier());
    }

    public function testUpdateIsWriteGatedAndDoesNotRequireConfirmation(): void
    {
        $this->assertSame(WriteMode::WRITE, $this->tool->getWriteMode());
        $this->assertFalse($this->tool->getConfirmationRequired());
    }

    public function testToolIdentity(): void
    {
        $this->assertSame('tax.rate.update', $this->tool->getName());
        $this->assertSame('Risecommerce_McpTaxTools::tool_tax_rate_update', $this->tool->getAclResource());
        $this->assertSame('Magento_Tax::manage_tax', $this->tool->getUnderlyingAclResource());
    }

    public function testAuditSummaryRecordsThePriorScalarFieldsBeforeApplying(): void
    {
        $rate = $this->createMock(TaxRateInterface::class);
        $rate->method('getId')->willReturn(3);
        $rate->method('getCode')->willReturn('US-MI');
        $rate->method('getTaxCountryId')->willReturn('US');
        $rate->method('getTaxRegionId')->willReturn(26);
        $rate->method('getTaxPostcode')->willReturn('*');
        $rate->method('getRate')->willReturn(6.0);
        $rate->method('getZipIsRange')->willReturn(0);
        $rate->method('getZipFrom')->willReturn(null);
        $rate->method('getZipTo')->willReturn(null);

        $this->rateRepository->method('get')->with(3)->willReturn($rate);
        $this->rateRepository->method('save')->willReturnArgument(0);

        $result = $this->tool->execute(['rate_id' => 3, 'rate' => 7.0]);

        $this->assertSame([
            'code' => 'US-MI',
            'tax_country_id' => 'US',
            'tax_region_id' => 26,
            'tax_postcode' => '*',
            'rate' => 6.0,
            'zip_is_range' => 0,
            'zip_from' => null,
            'zip_to' => null,
        ], $result->getAuditSummary()['before']);
    }
}
