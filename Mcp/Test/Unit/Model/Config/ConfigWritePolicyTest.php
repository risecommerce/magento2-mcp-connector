<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Model\Config;

use Risecommerce\Mcp\Model\Config\ConfigWriteConfig;
use Risecommerce\Mcp\Model\Config\ConfigWritePolicy;
use Risecommerce\Mcp\Model\Util\ConfigPathFormat;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class ConfigWritePolicyTest extends TestCase
{
    /**
     * @param list<string> $allowed
     * @param bool $enabled
     * @return ConfigWritePolicy
     */
    private function policy(array $allowed, bool $enabled = true): ConfigWritePolicy
    {
        $config = $this->createMock(ConfigWriteConfig::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getAllowedPaths')->willReturn($allowed);

        return new ConfigWritePolicy($config, new ConfigPathFormat(), [
            'web/unsecure',
            'web/secure',
            'admin',
            'payment',
            'system',
            'dev',
            'oauth',
            'risecommerce_mcp',
            // Contributed by Risecommerce_McpDbTools' di.xml, which pins the entry on its own side.
            'risecommerce_mcp_db_tools',
        ]);
    }

    public function testAnAllowlistedPathIsWritable(): void
    {
        $this->expectNotToPerformAssertions();
        $this->policy(['tax/calculation/based_on'])->assertWritable('tax/calculation/based_on');
    }

    public function testAPathOutsideTheAllowlistIsRefused(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/not in the configured allowlist/');
        $this->policy(['tax/calculation/based_on'])->assertWritable('tax/defaults/country');
    }

    public function testAnEmptyAllowlistRefusesEverything(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/No configuration paths are writable/');
        $this->policy([])->assertWritable('tax/calculation/based_on');
    }

    public function testADisabledToolRefusesEverything(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/Configuration writing is disabled/');
        $this->policy(['tax/calculation/based_on'], false)->assertWritable('tax/calculation/based_on');
    }

    public function testProtectedPathIsRefusedEvenWhenAllowlisted(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/is protected by "payment"/');
        $this->policy(['payment/checkmo/active'])->assertWritable('payment/checkmo/active');
    }

    public function testTheModuleCannotWidenItsOwnGatesEvenWhenAllowlisted(): void
    {
        $policy = $this->policy([
            'risecommerce_mcp/general/allow_writes',
            'risecommerce_mcp/config_write/allowed_paths',
            'risecommerce_mcp_db_tools/general/allowed_tables',
        ]);

        foreach ([
            'risecommerce_mcp/general/allow_writes',
            'risecommerce_mcp/config_write/allowed_paths',
            'risecommerce_mcp_db_tools/general/allowed_tables',
        ] as $path) {
            try {
                $policy->assertWritable($path);
                $this->fail(sprintf('Expected "%s" to be refused as protected.', $path));
            } catch (LocalizedException $e) {
                $this->assertStringContainsString('is protected by "risecommerce_mcp', $e->getMessage());
            }
        }
    }

    public function testPrefixMatchingDoesNotOverreachIntoUnrelatedSections(): void
    {
        $this->expectNotToPerformAssertions();
        // "developer/..." must not be caught by the "dev" protected prefix.
        $this->policy(['developer/mode/flag'])->assertWritable('developer/mode/flag');
    }

    public function testProtectedPrefixIsReportedForDiagnostics(): void
    {
        $this->assertSame('payment', $this->policy([])->findProtectedPrefix('payment/checkmo/active'));
        $this->assertNull($this->policy([])->findProtectedPrefix('tax/calculation/based_on'));
    }

    /**
     * @param string $path
     * @return void
     * @dataProvider malformedPathProvider
     */
    public function testAMalformedPathIsRefusedByTheShapeGuard(string $path): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/is not a well-formed configuration path/');
        // Allowlisted verbatim, and enabled, so only the shape guard can refuse it.
        $this->policy([$path])->assertWritable($path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedPathProvider(): array
    {
        return [
            'leading space' => [' admin/x'],
            'trailing space' => ['tax/calculation/based_on '],
            'leading tab' => ["\tadmin/x"],
            'leading newline' => ["\nadmin/x"],
            // Without the /D modifier PCRE lets "$" match before a final newline, so this one
            // slips the guard and Task 5's canonicalisation would write the trimmed path.
            'trailing newline' => ["admin/x\n"],
            'embedded space' => ['tax/calculation /based_on'],
            'doubled slash' => ['tax//based_on'],
            'leading slash' => ['/admin/x'],
            'trailing slash' => ['tax/calculation/'],
            'single segment' => ['tax'],
            'empty string' => [''],
            'dot segment' => ['tax/../admin/x'],
            'null byte' => ["tax/calculation/based_on\0"],
        ];
    }

    public function testMixedCasePathsSurviveTheShapeGuardAndStillHitTheProtectedLayer(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/is protected by "payment"/');
        $this->policy(['Payment/Checkmo/Active'])->assertWritable('Payment/Checkmo/Active');
    }

    /**
     * Folding case here would widen the allowlist, so the comparison must stay exact even though
     * the protected layer above it deliberately folds.
     *
     * @return void
     */
    public function testTheAllowlistComparisonIsExactAndNeverFoldsCase(): void
    {
        $policy = $this->policy(['Tax/Foo/Bar']);

        // Control: the entry as written is writable, so the refusals below are about case only.
        $policy->assertWritable('Tax/Foo/Bar');

        foreach (['tax/foo/bar', 'TAX/FOO/BAR', 'Tax/Foo/bar', 'tax/Foo/Bar'] as $variant) {
            try {
                $policy->assertWritable($variant);
                $this->fail(sprintf('Expected "%s" to be refused: the allowlist holds "Tax/Foo/Bar".', $variant));
            } catch (LocalizedException $e) {
                $this->assertStringContainsString('not in the configured allowlist', $e->getMessage());
            }
        }
    }

    public function testTheRejectionMessageDoesNotEchoRawCallerBytes(): void
    {
        $path = "ev\x00il\r\n\x1b[31m/" . str_repeat('a', 500);

        try {
            $this->policy([])->assertWritable($path);
            $this->fail('Expected the shape guard to refuse the path.');
        } catch (LocalizedException $e) {
            $message = $e->getMessage();
            $this->assertSame(0, preg_match('/[[:cntrl:]]/', $message), 'Control characters were echoed back.');
            $this->assertStringNotContainsString(str_repeat('a', 200), $message, 'The path was not truncated.');
            $this->assertStringContainsString('...', $message);
            $this->assertLessThan(400, strlen($message));
        }
    }

    /**
     * @param string $path
     * @return void
     * @dataProvider whitespacePaddedProtectedPathProvider
     */
    public function testFindProtectedPrefixIsNotFooledByWhitespace(string $path): void
    {
        $this->assertSame('admin', $this->policy([])->findProtectedPrefix($path));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function whitespacePaddedProtectedPathProvider(): array
    {
        return [
            'leading space' => [' admin/x'],
            'leading tab' => ["\tadmin/x"],
            'leading newline' => ["\nadmin/x"],
            'trailing space' => ['admin/x '],
            'leading slash' => ['/admin/x'],
            'mixed case' => ['Admin/X'],
        ];
    }
}
