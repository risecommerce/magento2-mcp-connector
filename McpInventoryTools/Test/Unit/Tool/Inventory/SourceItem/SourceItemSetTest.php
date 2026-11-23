<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Test\Unit\Tool\Inventory\SourceItem;

use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpInventoryTools\Model\ServiceErrorMessage;
use Risecommerce\McpInventoryTools\Tool\Inventory\SourceItem\SourceItemSet;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use PHPUnit\Framework\TestCase;

class SourceItemSetTest extends TestCase
{
    /** @var string[]|null Set-call order, used to pair SKUs with statuses. */
    private ?array $recordedSkus = null;

    /** @var int[]|null */
    private ?array $recordedStatuses = null;

    protected function setUp(): void
    {
        $this->recordedSkus = [];
        $this->recordedStatuses = [];
    }

    public function testToolMetadata(): void
    {
        $tool = $this->tool($this->createMock(SourceItemsSaveInterface::class));

        $this->assertSame('inventory.source_item.set', $tool->getName());
        $this->assertSame(
            'Risecommerce_McpInventoryTools::tool_inventory_source_item_set',
            $tool->getAclResource()
        );
        $this->assertSame(WriteMode::WRITE, $tool->getWriteMode());
        $this->assertTrue($tool->getConfirmationRequired());
        $this->assertSame(
            'Magento_InventoryApi::stock_source_item_assign',
            $tool->getUnderlyingAclResource()
        );
    }

    public function testSchemaHasNoCompositionKeywords(): void
    {
        $schema = $this->tool($this->createMock(SourceItemsSaveInterface::class))->getInputSchema();

        $this->assertSame('object', $schema['type']);
        foreach (['oneOf', 'anyOf', 'allOf', '$ref'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $schema);
        }
    }

    /**
     * The whole batch must reach MSI in one save call, not one call per row.
     */
    public function testSavesEveryItemInASingleCall(): void
    {
        $save = $this->createMock(SourceItemsSaveInterface::class);
        $save->expects($this->once())
            ->method('execute')
            ->with($this->countOf(2));

        $result = $this->tool($save)->execute([
            'items' => [
                ['sku' => 'a', 'source_code' => 'default', 'quantity' => 5],
                ['sku' => 'b', 'source_code' => 'warehouse', 'quantity' => 0],
            ],
        ]);

        $payload = $this->decode($result->getContent());
        $this->assertSame(2, $payload['saved']);
        $this->assertSame(0, $payload['skipped']);
    }

    public function testStatusDefaultsFromQuantity(): void
    {
        $this->tool($this->createMock(SourceItemsSaveInterface::class))->execute([
            'items' => [
                ['sku' => 'in-stock', 'source_code' => 'default', 'quantity' => 3],
                ['sku' => 'zero', 'source_code' => 'default', 'quantity' => 0],
            ],
        ]);

        $bySku = array_combine($this->recordedSkus ?? [], $this->recordedStatuses ?? []);
        $this->assertSame(1, $bySku['in-stock']);
        $this->assertSame(0, $bySku['zero']);
    }

    /**
     * Configurable/bundle/grouped SKUs carry no source items of their own, so
     * they are reported rather than pushed into MSI where they would throw.
     */
    public function testSkipsCompositeProducts(): void
    {
        $save = $this->createMock(SourceItemsSaveInterface::class);
        $save->expects($this->once())->method('execute')->with($this->countOf(1));

        $result = $this->tool($save, ['composite'])->execute([
            'items' => [
                ['sku' => 'simple', 'source_code' => 'default', 'quantity' => 5],
                ['sku' => 'composite', 'source_code' => 'default', 'quantity' => 5],
            ],
        ]);

        $payload = $this->decode($result->getContent());
        $this->assertSame(1, $payload['saved']);
        $this->assertSame(1, $payload['skipped']);
        $this->assertSame('skipped', $this->resultRow($payload, 1)['status']);
    }

    public function testSkipsItemMissingSourceCode(): void
    {
        $save = $this->createMock(SourceItemsSaveInterface::class);
        $save->expects($this->never())->method('execute');

        $result = $this->tool($save)->execute([
            'items' => [['sku' => 'a', 'quantity' => 5]],
        ]);

        $payload = $this->decode($result->getContent());
        $this->assertSame(0, $payload['saved']);
        $this->assertSame(1, $payload['skipped']);
    }

    /**
     * @param SourceItemsSaveInterface $save
     * @param string[] $compositeSkus
     * @return SourceItemSet
     */
    private function tool(SourceItemsSaveInterface $save, array $compositeSkus = []): SourceItemSet
    {
        $factory = $this->createMock(SourceItemInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(
            function (): SourceItemInterface {
                $item = $this->createMock(SourceItemInterface::class);
                $item->method('setSku')->willReturnCallback(
                    function ($sku): void {
                        $this->recordedSkus[] = (string) $sku;
                    }
                );
                $item->method('setStatus')->willReturnCallback(
                    function ($status): void {
                        $this->recordedStatuses[] = (int) $status;
                    }
                );

                return $item;
            }
        );

        $allowed = $this->createMock(IsSourceItemManagementAllowedForSkuInterface::class);
        $allowed->method('execute')->willReturnCallback(
            fn (string $sku): bool => !in_array($sku, $compositeSkus, true)
        );

        return new SourceItemSet($save, $factory, $allowed, new ServiceErrorMessage());
    }

    /**
     * @param array $content
     * @phpstan-param array<int, array<string, mixed>> $content
     * @return array<string, mixed>
     */
    private function decode(array $content): array
    {
        $text = $content[0]['text'] ?? null;
        $this->assertIsString($text);

        $decoded = json_decode($text, true);
        $this->assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array $payload
     * @phpstan-param array<string, mixed> $payload
     * @param int $index
     * @return array<string, mixed>
     */
    private function resultRow(array $payload, int $index): array
    {
        $results = $payload['results'] ?? null;
        $this->assertIsArray($results);

        $row = $results[$index] ?? null;
        $this->assertIsArray($row);

        /** @var array<string, mixed> $row */
        return $row;
    }
}
