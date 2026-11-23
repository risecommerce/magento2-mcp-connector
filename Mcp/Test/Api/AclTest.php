<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Api;

use Risecommerce\Mcp\Model\JsonRpc\ErrorCode;

/**
 * ACL enforcement: an admin whose role lacks the tool's resource
 * gets `-32004 FORBIDDEN`, even though their bearer is otherwise valid.
 *
 * @magentoApiDataFixture Risecommerce_Mcp::Test/Api/_files/limited_admin.php
 */
class AclTest extends McpTestCase
{
    /** Skip the default `adminUser` token — this test mints its own for `limitedAdmin`. */
    protected ?bool $issueToken = false;

    public function testMissingToolAclReturnsForbidden(): void
    {
        $issued = TokenFixture::issueForAdmin('limitedAdmin');
        $this->bearerToken = $issued['token'];
        $this->tokenRowId = $issued['id'];

        $response = $this->toolsCall('system.store.list');

        $this->assertJsonRpcError($response, ErrorCode::FORBIDDEN->value, 200);
    }

    public function testToolsListIsFilteredByAcl(): void
    {
        $issued = TokenFixture::issueForAdmin('limitedAdmin');
        $this->bearerToken = $issued['token'];
        $this->tokenRowId = $issued['id'];

        $response = $this->toolsList();

        $this->assertJsonRpcSuccess($response);
        $body = $response['body'];
        self::assertIsArray($body);
        $result = $body['result'] ?? null;
        self::assertIsArray($result);
        $tools = $result['tools'] ?? null;
        self::assertIsArray($tools);
        $names = array_column($tools, 'name');
        self::assertNotContains(
            'system_store_list',
            $names,
            'Tool with denied ACL must not appear in tools/list output.'
        );
    }
}
