<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\OAuth;

use Magento\Framework\Phrase;

/**
 * Per-client mapping of OAuth consents to Magento admin users — PERSONAL means
 * each admin authorizes for themselves, SHARED pins all consents to one service
 * admin (`service_admin_user_id`).
 */
enum AuthMode: string
{
    case PERSONAL = 'personal';
    case SHARED = 'shared';

    /**
     * @param string|null $value
     * @return self
     */
    public static function fromStorage(?string $value): self
    {
        if ($value === null) {
            return self::PERSONAL;
        }
        return self::tryFrom($value) ?? self::PERSONAL;
    }

    /**
     * @return Phrase
     */
    public function label(): Phrase
    {
        return match ($this) {
            self::PERSONAL => __('Personal (each admin authorizes for themselves)'),
            self::SHARED => __('Shared organization connector'),
        };
    }
}
