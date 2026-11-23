<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Console\Command;

use Risecommerce\Mcp\Api\Data\OAuth\ClientInterface;
use Risecommerce\Mcp\Console\Command\OAuthClientListCommand;
use Risecommerce\Mcp\Model\Auth\AdminUserLookup;
use Risecommerce\Mcp\Model\OAuth\AuthMode;
use Risecommerce\Mcp\Model\OAuth\Client;
use Risecommerce\Mcp\Model\OAuth\ClientRepository;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class OAuthClientListCommandTest extends TestCase
{
    /**
     * @phpstan-var ClientRepository&MockObject
     */
    // phpcs:ignore Magento2.Commenting.ClassPropertyPHPDocFormatting
    private ClientRepository&MockObject $clientRepository;

    /**
     * @phpstan-var AdminUserLookup&MockObject
     */
    // phpcs:ignore Magento2.Commenting.ClassPropertyPHPDocFormatting
    private AdminUserLookup&MockObject $adminUserLookup;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->adminUserLookup = $this->createMock(AdminUserLookup::class);
        $this->adminUserLookup->method('listByIds')->willReturn([]);
    }

    public function testReportsAnEmptyList(): void
    {
        $this->clientRepository->method('getList')->willReturn([]);

        $tester = $this->runCommand();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No OAuth clients', $tester->getDisplay());
    }

    public function testPrintsTheClientIdAndStatus(): void
    {
        $this->clientRepository->method('getList')->willReturn([
            $this->makeClient(1, 'Claude Web', 'uuid-1', ['catalog.product.list']),
        ]);

        $display = $this->runCommand()->getDisplay();

        self::assertStringContainsString('Claude Web', $display);
        self::assertStringContainsString('uuid-1', $display);
        self::assertStringContainsString('enabled', $display);
    }

    public function testDisabledClientsAreMarked(): void
    {
        $this->clientRepository->method('getList')->willReturn([
            $this->makeClient(1, 'Old', 'uuid-1', ['catalog.product.list'], disabled: true),
        ]);

        self::assertStringContainsString('disabled', $this->runCommand()->getDisplay());
    }

    public function testAllToolsSentinelIsRenderedReadably(): void
    {
        $this->clientRepository->method('getList')->willReturn([
            $this->makeClient(1, 'Claude Web', 'uuid-1', [ClientInterface::ALLOW_ALL_TOOLS_SENTINEL]),
        ]);

        $display = $this->runCommand()->getDisplay();

        self::assertStringContainsString('all', $display);
        self::assertStringNotContainsString('| *', $display);
    }

    public function testSharedModeShowsThePinnedServiceAdminUsername(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUsername')->willReturn('svc');
        $lookup = $this->createMock(AdminUserLookup::class);
        $lookup->method('listByIds')->willReturn([12 => $user]);
        $this->adminUserLookup = $lookup;

        $this->clientRepository->method('getList')->willReturn([
            $this->makeClient(1, 'Shared', 'uuid-1', ['sales.order.get'], serviceAdminUserId: 12),
        ]);

        $display = $this->runCommand()->getDisplay();

        self::assertStringContainsString('shared', $display);
        self::assertStringContainsString('svc', $display);
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $clientId
     * @param array<int, string> $tools
     * @param bool $disabled
     * @param int|null $serviceAdminUserId
     * @return Client&MockObject
     */
    private function makeClient(
        int $id,
        string $name,
        string $clientId,
        array $tools,
        bool $disabled = false,
        ?int $serviceAdminUserId = null
    ): Client&MockObject {
        $client = $this->createMock(Client::class);
        $client->method('getId')->willReturn($id);
        $client->method('getName')->willReturn($name);
        $client->method('getClientId')->willReturn($clientId);
        $client->method('getAllowedTools')->willReturn($tools);
        $client->method('getRedirectUris')->willReturn(['https://claude.ai/api/mcp/auth_callback']);
        $client->method('isDisabled')->willReturn($disabled);
        $client->method('getCreatedAt')->willReturn('2026-08-31 10:00:00');
        $client->method('getServiceAdminUserId')->willReturn($serviceAdminUserId);
        $client->method('getAuthMode')->willReturn(
            $serviceAdminUserId === null ? AuthMode::PERSONAL : AuthMode::SHARED
        );
        $client->method('getAllowedAdminUserIds')->willReturn([]);
        $client->method('getAllowedAdminRoleIds')->willReturn([]);
        return $client;
    }

    /**
     * @return CommandTester
     */
    private function runCommand(): CommandTester
    {
        $tester = new CommandTester(
            new OAuthClientListCommand($this->clientRepository, $this->adminUserLookup)
        );
        $tester->execute([]);
        return $tester;
    }
}
