<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Block\Adminhtml\Prompt;

use Risecommerce\Mcp\Block\Adminhtml\AbstractEdit\Form;
use Risecommerce\Mcp\Controller\Adminhtml\Prompt\Edit as EditController;
use Risecommerce\Mcp\Model\Prompt\AdminPrompt;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

/**
 * Form\Container for the prompt New / Edit pages. Pairs with the Tabs widget
 * in the `left` container so the rendered tab panels are moved into this
 * form's `<form id="edit_form">` element by mage/backend/tabs.js.
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
        $this->_controller = 'adminhtml_prompt';
        $this->_blockGroup = 'Risecommerce_Mcp';
        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->update('save', 'label', __('Save Prompt'));
        $this->buttonList->update('back', 'label', __('Back'));
        $this->buttonList->update('back', 'onclick', sprintf(
            "location.href = '%s';",
            $this->getUrl('risecommerce_mcp/prompt/index')
        ));
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        $prompt = $this->registry->registry(EditController::REGISTRY_KEY);
        if ($prompt instanceof AdminPrompt) {
            return (string) __('Edit Prompt "%1"', $prompt->getTitle());
        }
        return (string) __('New Prompt');
    }

    /**
     * @return string
     */
    public function getFormActionUrl(): string
    {
        return $this->getUrl('risecommerce_mcp/prompt/save');
    }

    /**
     * @return string
     */
    protected function _buildFormClassName()
    {
        return Form::class;
    }
}
