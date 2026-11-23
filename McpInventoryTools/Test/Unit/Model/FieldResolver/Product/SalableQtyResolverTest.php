<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Test\Unit\Model\FieldResolver\Product;

use Risecommerce\McpInventoryTools\Model\FieldResolver\Product\SalableQtyResolver;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use PHPUnit\Framework\TestCase;

class SalableQtyResolverTest extends TestCase
{
    public function testKeyAndSortOrder(): void
    {
        $resolver = $this->resolver();

        $this->assertSame('salable_qty', $resolver->getKey());
        // Sits directly after the catalog module's own `stock` slice (40).
        $this->assertSame(41, $resolver->getSortOrder());
    }

    public function testReportsSalableQtyAndReservationDelta(): void
    {
        $salable = $this->createMock(GetProductSalableQtyInterface::class);
        $salable->method('execute')->with('sku-1', 1)->willReturn(8.0);

        $reservations = $this->createMock(GetReservationsQuantityInterface::class);
        $reservations->method('execute')->with('sku-1', 1)->willReturn(-2.0);

        $result = $this->resolver($salable, $reservations)->resolve($this->product('sku-1'), []);

        // The pipeline keys the slice by getKey(), so resolve() must return the
        // payload flat — wrapping it again yields salable_qty.salable_qty.
        $this->assertSame(1, $result['stock_id']);
        $this->assertSame(8.0, $result['qty']);
        // Reservations are stored negative; the slice reports the amount held.
        $this->assertSame(2.0, $result['reserved']);
        $this->assertArrayNotHasKey('salable_qty', $result);
    }

    /**
     * Composite types have no salable quantity and MSI throws; the slice has to
     * degrade to null rather than break the whole product response.
     */
    public function testReturnsNullWhenMsiCannotAnswer(): void
    {
        $salable = $this->createMock(GetProductSalableQtyInterface::class);
        $salable->method('execute')->willThrowException(new InputException(__('composite')));

        $result = $this->resolver($salable)->resolve($this->product('bundle-1'), []);

        $this->assertNull($result['qty']);
        $this->assertNull($result['reserved']);
    }

    public function testReturnsNullForBlankSku(): void
    {
        $result = $this->resolver()->resolve($this->product(''), []);

        $this->assertNull($result['stock_id']);
        $this->assertNull($result['qty']);
    }

    public function testHonoursExplicitStockId(): void
    {
        $salable = $this->createMock(GetProductSalableQtyInterface::class);
        $salable->expects($this->once())->method('execute')->with('sku-1', 5)->willReturn(1.0);

        $this->resolver($salable)->resolve($this->product('sku-1'), ['stock_id' => 5]);
    }

    /**
     * @param GetProductSalableQtyInterface|null $salable
     * @param GetReservationsQuantityInterface|null $reservations
     * @return SalableQtyResolver
     */
    private function resolver(
        ?GetProductSalableQtyInterface $salable = null,
        ?GetReservationsQuantityInterface $reservations = null
    ): SalableQtyResolver {
        $defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $defaultStockProvider->method('getId')->willReturn(1);

        return new SalableQtyResolver(
            $salable ?? $this->createMock(GetProductSalableQtyInterface::class),
            $reservations ?? $this->createMock(GetReservationsQuantityInterface::class),
            $defaultStockProvider
        );
    }

    /**
     * @param string $sku
     * @return ProductInterface
     */
    private function product(string $sku): ProductInterface
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getSku')->willReturn($sku);

        return $product;
    }
}
