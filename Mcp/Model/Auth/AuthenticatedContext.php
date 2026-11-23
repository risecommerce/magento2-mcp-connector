<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Auth;

use Risecommerce\Mcp\Api\Data\TokenInterface;
use Magento\User\Model\User;

/**
 * Result of a successful MCP authentication; references the admin user that
 * owns the bearer (never the Magento session user).
 */
class AuthenticatedContext
{
    /**
     * @param TokenInterface $token
     * @param User $adminUser
     */
    public function __construct(
        public readonly TokenInterface $token,
        public readonly User $adminUser
    ) {
    }

    /**
     * @return int
     */
    public function getAdminUserId(): int
    {
        $id = $this->adminUser->getId();
        return is_scalar($id) ? (int) $id : 0;
    }
}
