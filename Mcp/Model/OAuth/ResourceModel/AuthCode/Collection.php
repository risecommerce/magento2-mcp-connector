<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\OAuth\ResourceModel\AuthCode;

use Risecommerce\Mcp\Model\OAuth\AuthCode;
use Risecommerce\Mcp\Model\OAuth\ResourceModel\AuthCode as AuthCodeResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method AuthCode[] getItems()
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(AuthCode::class, AuthCodeResource::class);
    }
}
