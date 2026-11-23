<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Block\Adminhtml\Token;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;

/**
 * Form\Container for the New MCP Connection page. Pairs with the Tabs widget
 * in the `left` container so the rendered tab panels are moved into this
 * form's `<form id="edit_form">` element by mage/backend/tabs.js.
 */
class Edit extends Container
{
    /**
     * @param Context $context
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_token';
        $this->_blockGroup = 'Risecommerce_Mcp';
        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->update('back', 'label', __('Back'));
        $this->buttonList->update('back', 'onclick', sprintf(
            "location.href = '%s';",
            $this->getUrl('risecommerce_mcp/token/index')
        ));
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        return (string) __('New MCP Connection');
    }

    public function getFormActionUrl(): string
    {
        return $this->getUrl('risecommerce_mcp/token/save');
    }
}
