<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\OAuth\ResourceModel\RefreshToken;

use Risecommerce\Mcp\Model\OAuth\RefreshToken;
use Risecommerce\Mcp\Model\OAuth\ResourceModel\RefreshToken as RefreshTokenResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method RefreshToken[] getItems()
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
        $this->_init(RefreshToken::class, RefreshTokenResource::class);
    }
}
