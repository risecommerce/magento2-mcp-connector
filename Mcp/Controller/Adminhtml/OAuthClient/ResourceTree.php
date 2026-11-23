<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Controller\Adminhtml\OAuthClient;

use Risecommerce\Mcp\Helper\Acl\ToolResourceTree;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * AJAX endpoint backing the OAuth client "Allowed Tools" jstree. Always emits
 * the unrestricted tree — clients are admin-agnostic, so the picker shows
 * every registered tool. The consent screen is what intersects with the
 * approving admin's role at runtime.
 */
class ResourceTree extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Risecommerce_Mcp::mcp_oauth_clients';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ToolResourceTree $toolResourceTree
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly ToolResourceTree $toolResourceTree
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->jsonFactory->create();
        $result->setData(['tree' => $this->toolResourceTree->buildUnrestricted()]);
        return $result;
    }
}
