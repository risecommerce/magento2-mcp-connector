<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\AuditLog;

/**
 * Mutable, request-scoped bag the audit logger flushes.
 *
 * Controller fills environment fields (IP, UA, protocol version, request id,
 * method); handler fills tool-specific fields; logger writes whatever is set.
 * A single mutable DTO lets the controller's `finally` emit an audit row even
 * on pre-auth failures.
 */
class AuditContext
{
    public const METHOD_UNPARSED = '(request)';

    /** @var int|null */
    public ?int $tokenId = null;

    /** @var int|null */
    public ?int $adminUserId = null;

    /** @var int|string|null */
    // phpcs:ignore Magento2.Commenting.ClassPropertyPHPDocFormatting
    public int|string|null $requestId = null;

    /** @var string|null */
    public ?string $protocolVersion = null;

    /** @var string */
    public string $method = self::METHOD_UNPARSED;

    /** @var string|null */
    public ?string $toolName = null;

    /** @var string|null */
    public ?string $promptName = null;

    /**
     * @var array<int|string, mixed>|null
     */
    public ?array $arguments = null;

    /**
     * @var array<int|string, mixed>|null
     */
    public ?array $resultSummary = null;

    /** @var string */
    public string $responseStatus = 'ok';

    /** @var string|null */
    public ?string $errorCode = null;

    /** @var int|null */
    public ?int $durationMs = null;

    /** @var string|null */
    public ?string $ipAddress = null;

    /** @var string|null */
    public ?string $userAgent = null;
}
