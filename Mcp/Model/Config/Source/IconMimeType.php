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
 * Icon MIME types valid for `initialize.serverInfo.icons[].mimeType`.
 */
class IconMimeType implements OptionSourceInterface
{
    /**
     * @inheritDoc
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => '-- Select MIME type --'],
            ['value' => 'image/png', 'label' => 'image/png'],
            ['value' => 'image/svg+xml', 'label' => 'image/svg+xml'],
            ['value' => 'image/jpeg', 'label' => 'image/jpeg'],
            ['value' => 'image/webp', 'label' => 'image/webp'],
        ];
    }
}
