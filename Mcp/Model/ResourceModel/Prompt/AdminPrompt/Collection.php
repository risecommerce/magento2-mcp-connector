<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\ResourceModel\Prompt\AdminPrompt;

use Risecommerce\Mcp\Model\Prompt\AdminPrompt;
use Risecommerce\Mcp\Model\ResourceModel\Prompt\AdminPrompt as AdminPromptResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method AdminPrompt[] getItems()
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(AdminPrompt::class, AdminPromptResource::class);
    }
}
