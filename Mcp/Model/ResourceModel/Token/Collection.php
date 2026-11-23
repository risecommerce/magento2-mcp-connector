<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\ResourceModel\Token;

use Risecommerce\Mcp\Model\ResourceModel\Token as TokenResource;
use Risecommerce\Mcp\Model\Token;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method Token[] getItems()
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
        $this->_init(Token::class, TokenResource::class);
    }
}
