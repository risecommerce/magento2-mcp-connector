<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Ui\Component\OAuthClient\Listing\Column;

use Risecommerce\Mcp\Model\OAuth\AuthMode;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options source for the OAuth Clients listing's "Mode" column.
 */
class AuthModeOptions implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => AuthMode::PERSONAL->value, 'label' => __('Personal')->render()],
            ['value' => AuthMode::SHARED->value, 'label' => __('Shared')->render()],
        ];
    }
}
