<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Block\Adminhtml\OAuthClient;

use Risecommerce\Mcp\Api\Data\OAuth\ClientInterface;
use Risecommerce\Mcp\Block\Adminhtml\OAuthClient\Edit\Form;
use Risecommerce\Mcp\Controller\Adminhtml\OAuthClient\Edit as EditController;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

/**
 * Form\Container for the OAuth client New / Edit pages.
 */
class Edit extends Container
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_oauthclient';
        $this->_blockGroup = 'Risecommerce_Mcp';
        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->update('save', 'label', __('Save Client'));
        $this->buttonList->update('back', 'label', __('Back'));
        $this->buttonList->update('back', 'onclick', sprintf(
            "location.href = '%s';",
            $this->getUrl('risecommerce_mcp/oauthclient/index')
        ));

        // Hide on the new-client page — nothing to rotate yet.
        if ($this->registry->registry(EditController::REGISTRY_KEY) instanceof ClientInterface) {
            $this->buttonList->add('rotate_secret', [
                'label' => __('Rotate Secret'),
                'class' => 'action-secondary',
                'data_attribute' => [
                    'mcp-action' => 'rotate-secret',
                ],
            ], -1, 15);
        }
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        $client = $this->registry->registry(EditController::REGISTRY_KEY);
        if ($client instanceof ClientInterface) {
            return (string) __('Edit OAuth Client "%1"', $client->getName());
        }
        return (string) __('New OAuth Client');
    }

    /**
     * Edit-mode carries the id as a URL param so the Save controller routes to the
     * update branch regardless of what the form fields POST. The hidden form field
     * approach was lost by `AbstractForm::setValues()` nulling unmapped elements.
     *
     * @return string
     */
    public function getFormActionUrl(): string
    {
        $client = $this->registry->registry(EditController::REGISTRY_KEY);
        if ($client instanceof ClientInterface) {
            return $this->getUrl('risecommerce_mcp/oauthclient/save', ['id' => (int) $client->getId()]);
        }
        return $this->getUrl('risecommerce_mcp/oauthclient/save');
    }

    /**
     * @return string Override — default NameBuilder yields Adminhtml\Oauthclient,
     *                which fails to resolve on case-sensitive filesystems.
     */
    protected function _buildFormClassName()
    {
        return Form::class;
    }
}
