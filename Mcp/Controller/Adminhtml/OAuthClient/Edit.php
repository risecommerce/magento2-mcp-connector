<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Controller\Adminhtml\OAuthClient;

use Risecommerce\Mcp\Model\OAuth\Client;
use Risecommerce\Mcp\Model\OAuth\ClientRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Renders the OAuth client edit form. Both "new" (forwarded from {@see NewAction})
 * and "existing" (`?id=<n>`) modes flow through here. The loaded model is
 * registered under `current_oauth_client` so the form block can read it; null
 * means new-mode for the form's branch.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Risecommerce_Mcp::mcp_oauth_clients';
    public const REGISTRY_KEY = 'current_oauth_client';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param ClientRepository $clientRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly Registry $registry,
        private readonly ClientRepository $clientRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page|Redirect
     */
    public function execute(): ResultInterface
    {
        $idRaw = $this->getRequest()->getParam('id');
        $id = is_scalar($idRaw) ? (int) $idRaw : 0;

        $client = null;
        if ($id > 0) {
            try {
                $client = $this->clientRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(
                    (string) __('OAuth client #%1 not found.', $id)
                );
                /** @var Redirect $redirect */
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $redirect->setPath('*/*/index');
            }
        }

        $this->registry->register(self::REGISTRY_KEY, $client, true);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Risecommerce_Mcp::mcp_oauth_clients');
        $resultPage->addBreadcrumb((string) __('MCP'), (string) __('MCP'));
        $resultPage->addBreadcrumb(
            (string) __('OAuth Clients'),
            (string) __('OAuth Clients'),
            $this->getUrl('risecommerce_mcp/oauthclient/index')
        );

        if ($client instanceof Client) {
            $resultPage->addBreadcrumb((string) __('Edit'), (string) __('Edit'));
            $resultPage->getConfig()->getTitle()->prepend(
                (string) __('Edit OAuth Client "%1"', $client->getName())
            );
        } else {
            $resultPage->addBreadcrumb((string) __('New'), (string) __('New'));
            $resultPage->getConfig()->getTitle()->prepend((string) __('New OAuth Client'));
        }

        return $resultPage;
    }
}
