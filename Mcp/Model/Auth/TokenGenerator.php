<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Auth;

/**
 * Generates cryptographically random plaintext bearer tokens.
 * 32 bytes = 256 bits of entropy → 64 hex characters.
 */
class TokenGenerator
{
    private const BYTE_LENGTH = 32;

    /**
     * @return string
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(self::BYTE_LENGTH));
    }
}
