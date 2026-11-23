<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Block\Adminhtml\Token;

use Magento\Backend\Block\Template;

/**
 * One-shot post-create plaintext bearer display. Plaintext is passed in by the
 * Save controller via {@see setData()}; never persisted to session storage.
 */
class Created extends Template
{
    public function getName(): string
    {
        $v = $this->getData('name');
        return is_string($v) ? $v : '';
    }

    public function getPlaintext(): string
    {
        $v = $this->getData('plaintext');
        return is_string($v) ? $v : '';
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('risecommerce_mcp/token/index');
    }
}
