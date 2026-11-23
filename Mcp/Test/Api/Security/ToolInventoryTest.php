<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Api\Security;

use Risecommerce\Mcp\Api\ToolRegistryInterface;
use Risecommerce\Mcp\Model\Tool\DisabledTools;
use Risecommerce\Mcp\Test\Api\McpTestCase;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * A full-access token sees every registered tool except the disabled ones, and nothing else.
 *
 * @magentoApiDataFixture Magento/User/_files/user_with_role.php
 */
class ToolInventoryTest extends McpTestCase
{
    protected ?bool $allowWrites = true;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        ConfigState::set('risecommerce_mcp/general/allow_writes', '1');
        parent::setUp();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        ConfigState::restoreAll();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testToolsListMatchesRegistryMinusDisabled(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ToolRegistryInterface $registry */
        $registry = $objectManager->get(ToolRegistryInterface::class);
        /** @var DisabledTools $disabledTools */
        $disabledTools = $objectManager->create(DisabledTools::class);

        $expected = [];
        foreach ($registry->all() as $tool) {
            $name = $tool->getName();
            if (!$disabledTools->isDisabled($name)) {
                $expected[] = str_replace('.', '_', $name);
            }
        }
        sort($expected);

        $actual = ToolPayload::toolNames($this->toolsList());
        sort($actual);

        self::assertNotEmpty($expected, 'Registry returned no tools.');
        self::assertSame($expected, $actual, 'tools/list differs from the registry minus disabled tools.');
    }
}
