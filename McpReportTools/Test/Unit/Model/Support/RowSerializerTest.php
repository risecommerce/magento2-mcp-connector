<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpReportTools\Test\Unit\Model\Support;

use Risecommerce\McpReportTools\Model\Support\RowSerializer;
use PHPUnit\Framework\TestCase;

class RowSerializerTest extends TestCase
{
    public function testCastsNumericStringsToIntAndFloat(): void
    {
        $out = (new RowSerializer())->normalise([
            'qty' => '42',
            'grand_total' => '199.99',
            'sku' => 'ABC-001',
            'null_col' => null,
        ]);

        $this->assertSame(42, $out['qty']);
        $this->assertSame(199.99, $out['grand_total']);
        $this->assertSame('ABC-001', $out['sku']);
        $this->assertNull($out['null_col']);
    }

    public function testPreservesNonStringScalars(): void
    {
        $out = (new RowSerializer())->normalise([
            'bool' => true,
            'already_int' => 5,
        ]);

        $this->assertTrue($out['bool']);
        $this->assertSame(5, $out['already_int']);
    }

    public function testRecursesIntoNestedArrays(): void
    {
        $out = (new RowSerializer())->normalise([
            'totals' => ['grand' => '100', 'items' => '3'],
        ]);

        $this->assertSame(['grand' => 100, 'items' => 3], $out['totals']);
    }
}
