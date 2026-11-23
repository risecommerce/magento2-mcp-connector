<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\OAuth;

/**
 * Immutable carrier for the per-client authorization knobs: auth mode, pinned
 * service admin, optional personal-mode whitelists, and the disabled flag.
 */
final class AuthorizationOptions
{
    /**
     * @param AuthMode $mode
     * @param int|null $serviceAdminUserId
     * @param array<int, int> $allowedAdminUserIds
     * @param array<int, int> $allowedAdminRoleIds
     * @param bool $disabled
     */
    public function __construct(
        public readonly AuthMode $mode,
        public readonly ?int $serviceAdminUserId,
        public readonly array $allowedAdminUserIds,
        public readonly array $allowedAdminRoleIds,
        public readonly bool $disabled
    ) {
    }

    /**
     * @return self
     */
    public static function personalDefault(): self
    {
        return new self(
            mode: AuthMode::PERSONAL,
            serviceAdminUserId: null,
            allowedAdminUserIds: [],
            allowedAdminRoleIds: [],
            disabled: false
        );
    }
}
