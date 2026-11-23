<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Model\JsonRpc\Handler;

use Risecommerce\Mcp\Api\Data\AuditEntryInterface;
use Risecommerce\Mcp\Api\Data\TokenInterface;
use Risecommerce\Mcp\Api\LoggerInterface;
use Risecommerce\Mcp\Api\RateLimiterInterface;
use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolRegistryInterface;
use Risecommerce\Mcp\Exception\ForbiddenException;
use Risecommerce\Mcp\Model\Acl\AclChecker;
use Risecommerce\Mcp\Model\Auth\AuthenticatedContext;
use Risecommerce\Mcp\Model\Auth\AuthenticatedContextProvider;
use Risecommerce\Mcp\Model\AuditLog\AuditContext;
use Risecommerce\Mcp\Model\Config\ModuleConfig;
use Risecommerce\Mcp\Model\JsonRpc\ErrorCode;
use Risecommerce\Mcp\Model\JsonRpc\Handler\ToolsCallHandler;
use Risecommerce\Mcp\Model\JsonRpc\Request;
use Risecommerce\Mcp\Model\JsonRpc\Response;
use Risecommerce\Mcp\Model\Tool\DisabledTools;
use Risecommerce\Mcp\Model\Tool\SchemaSanitizer;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\Mcp\Model\Validator\ArgumentCoercer;
use Risecommerce\Mcp\Model\Validator\JsonSchemaValidator;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class ToolsCallHandlerForbiddenTest extends TestCase
{
    /**
     * @var AuditContext
     */
    private AuditContext $auditContext;

    /**
     * @var LoggerInterface&MockObject
     */
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->auditContext = new AuditContext();
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testForbiddenExceptionAnswersForbiddenWithMessageAndData(): void
    {
        $this->logger->expects($this->never())->method('error');

        $response = $this->call(new ForbiddenException(
            'Page 7 belongs to a store outside your admin scope.',
            ['reason' => 'store_scope', 'page_id' => 7]
        ));

        $this->assertNotNull($response->error);
        $this->assertSame(ErrorCode::FORBIDDEN, $response->error->code);
        $this->assertSame('Page 7 belongs to a store outside your admin scope.', $response->error->message);
        $this->assertSame(['reason' => 'store_scope', 'page_id' => 7], $response->error->data);
    }

    public function testForbiddenExceptionIsAuditedLikeThePreExecutionRefusals(): void
    {
        $this->call(new ForbiddenException('Not in your scope.'));

        $this->assertSame((string) ErrorCode::FORBIDDEN->value, $this->auditContext->errorCode);
        $this->assertSame('-32004', $this->auditContext->errorCode);
        $this->assertSame(AuditEntryInterface::STATUS_ERROR, $this->auditContext->responseStatus);
    }

    public function testForbiddenExceptionWithoutDataOmitsErrorData(): void
    {
        $response = $this->call(new ForbiddenException('Not in your scope.'));

        $this->assertNotNull($response->error);
        $this->assertSame(ErrorCode::FORBIDDEN, $response->error->code);
        $this->assertNull($response->error->data);
    }

    public function testLocalizedExceptionStillAnswersToolExecutionFailed(): void
    {
        $this->logger->expects($this->never())->method('error');

        $response = $this->call(new LocalizedException(new Phrase('The page could not be saved.')));

        $this->assertNotNull($response->error);
        $this->assertSame(ErrorCode::TOOL_EXECUTION_FAILED, $response->error->code);
        $this->assertSame('The page could not be saved.', $response->error->message);
        $this->assertSame('-32011', $this->auditContext->errorCode);
    }

    public function testUnexpectedExceptionStillAnswersGenericFailureAndIsLogged(): void
    {
        $this->logger->expects($this->once())->method('error');

        $response = $this->call(new RuntimeException('SQLSTATE[42S02]: table missing'));

        $this->assertNotNull($response->error);
        $this->assertSame(ErrorCode::TOOL_EXECUTION_FAILED, $response->error->code);
        $this->assertSame('Tool execution failed.', $response->error->message);
        $this->assertSame('-32011', $this->auditContext->errorCode);
    }

    /**
     * @param Throwable $thrown
     * @return Response
     */
    private function call(Throwable $thrown): Response
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('system.store.info');
        $tool->method('getAclResource')->willReturn('Risecommerce_Mcp::tool_system_store_info');
        $tool->method('getWriteMode')->willReturn(WriteMode::READ);
        $tool->method('getInputSchema')->willReturn(['type' => 'object']);
        $tool->expects($this->once())->method('execute')->willThrowException($thrown);

        $toolRegistry = $this->createMock(ToolRegistryInterface::class);
        $toolRegistry->method('getCanonicalName')->willReturn('system.store.info');
        $toolRegistry->method('get')->willReturn($tool);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getScopes')->willReturn(null);

        $adminUser = $this->createMock(User::class);
        $adminUser->method('getId')->willReturn(1);

        $aclChecker = $this->createMock(AclChecker::class);
        $aclChecker->method('isAllowed')->willReturn(true);

        $coercer = $this->createMock(ArgumentCoercer::class);
        $coercer->method('coerce')->willReturnArgument(1);

        $sanitizer = $this->createMock(SchemaSanitizer::class);
        $sanitizer->method('sanitize')->willReturn(['type' => 'object']);

        $disabledTools = $this->createMock(DisabledTools::class);
        $disabledTools->method('isDisabled')->willReturn(false);

        $handler = new ToolsCallHandler(
            $toolRegistry,
            $aclChecker,
            $this->createMock(JsonSchemaValidator::class),
            $this->createMock(RateLimiterInterface::class),
            $this->createMock(EventManager::class),
            $this->createMock(ModuleConfig::class),
            $this->auditContext,
            new AuthenticatedContextProvider(),
            $this->logger,
            $coercer,
            $sanitizer,
            $disabledTools
        );

        return $handler->handle(
            new Request(3, false, 'tools/call', ['name' => 'system.store.info', 'arguments' => []]),
            new AuthenticatedContext($token, $adminUser)
        );
    }
}
