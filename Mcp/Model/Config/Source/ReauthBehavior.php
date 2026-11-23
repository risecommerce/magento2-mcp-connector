<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source for `risecommerce_mcp/oauth/reauth_behavior` — what happens when the same
 * (client, admin) pair re-authorizes while a prior token is live.
 */
class ReauthBehavior implements OptionSourceInterface
{
    public const ALLOW_MULTIPLE = 'allow_multiple';
    public const ROTATE = 'rotate';
    public const REJECT = 'reject';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ALLOW_MULTIPLE,
                'label' => __('Allow multiple — keep prior token, issue a new one alongside')->render(),
            ],
            [
                'value' => self::ROTATE,
                'label' => __('Rotate — revoke prior token(s) for this client + admin, then issue')->render(),
            ],
            [
                'value' => self::REJECT,
                'label' => __('Reject — fail the grant when an active token already exists')->render(),
            ],
        ];
    }

    /**
     * @param mixed $value
     * @return string One of the three known values; falls back to ALLOW_MULTIPLE on null/unknown.
     */
    public static function normalize(mixed $value): string
    {
        if (!is_string($value)) {
            return self::ALLOW_MULTIPLE;
        }
        return match ($value) {
            self::ROTATE => self::ROTATE,
            self::REJECT => self::REJECT,
            default => self::ALLOW_MULTIPLE,
        };
    }
}
