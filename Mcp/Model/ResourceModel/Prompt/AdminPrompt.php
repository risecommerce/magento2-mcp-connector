<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\ResourceModel\Prompt;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for {@see \Risecommerce\Mcp\Model\Prompt\AdminPrompt}.
 */
class AdminPrompt extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('risecommerce_mcp_prompt', 'id');
    }
}
