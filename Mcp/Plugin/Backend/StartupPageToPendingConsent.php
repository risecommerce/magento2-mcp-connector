<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Plugin\Backend;

use Risecommerce\Mcp\Model\OAuth\PendingConsent;
use Risecommerce\Mcp\Observer\RememberPendingConsent;
use Magento\Backend\Model\UrlInterface;

/**
 * While a consent screen is pending (see {@see RememberPendingConsent}), the
 * admin startup page is that consent screen. Every two-factor provider sends
 * the admin to the startup page after a valid code, so this returns the admin
 * to the consent screen without depending on Magento_TwoFactorAuth.
 */
class StartupPageToPendingConsent
{
    /**
     * @param PendingConsent $pendingConsent
     */
    public function __construct(
        private readonly PendingConsent $pendingConsent
    ) {
    }

    /**
     * @param UrlInterface $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetStartupPageUrl(UrlInterface $subject, mixed $result): mixed
    {
        return $this->pendingConsent->getRoutePath() ?? $result;
    }
}
