<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Test\Unit\Plugin\Backend;

use Risecommerce\Mcp\Model\OAuth\PendingConsent;
use Risecommerce\Mcp\Plugin\Backend\StartupPageToPendingConsent;
use Magento\Backend\Model\UrlInterface;
use PHPUnit\Framework\TestCase;

class StartupPageToPendingConsentTest extends TestCase
{
    /**
     * @return void
     */
    public function testStartupPageIsUnchangedWithoutPendingConsent(): void
    {
        $pendingConsent = $this->createMock(PendingConsent::class);
        $pendingConsent->method('getRoutePath')->willReturn(null);
        $plugin = new StartupPageToPendingConsent($pendingConsent);

        self::assertSame(
            'adminhtml/dashboard',
            $plugin->afterGetStartupPageUrl($this->createMock(UrlInterface::class), 'adminhtml/dashboard')
        );
    }

    /**
     * @return void
     */
    public function testStartupPageIsThePendingConsentScreen(): void
    {
        $pendingConsent = $this->createMock(PendingConsent::class);
        $pendingConsent->method('getRoutePath')->willReturn('risecommerce_mcp/oauth/authorize/h/aabbccddeeff0011');
        $plugin = new StartupPageToPendingConsent($pendingConsent);

        self::assertSame(
            'risecommerce_mcp/oauth/authorize/h/aabbccddeeff0011',
            $plugin->afterGetStartupPageUrl($this->createMock(UrlInterface::class), 'adminhtml/dashboard')
        );
    }
}
