<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Controller\Adminhtml\Token;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Renders the "New MCP Connection" form. Tokens are mint-once (rotate-not-edit),
 * so there's no matching Edit controller — the form always posts to {@see Save}.
 */
class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Risecommerce_Mcp::mcp_tokens';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Risecommerce_Mcp::mcp_tokens');
        $resultPage->addBreadcrumb((string) __('MCP'), (string) __('MCP'));
        $resultPage->addBreadcrumb(
            (string) __('Connections'),
            (string) __('Connections'),
            $this->getUrl('risecommerce_mcp/token/index')
        );
        $resultPage->addBreadcrumb((string) __('New'), (string) __('New'));
        $resultPage->getConfig()->getTitle()->prepend((string) __('New MCP Connection'));
        return $resultPage;
    }
}
