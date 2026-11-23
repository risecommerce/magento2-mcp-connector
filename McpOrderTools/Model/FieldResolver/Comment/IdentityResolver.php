<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Model\FieldResolver\Comment;

use Risecommerce\McpOrderTools\Api\OrderCommentFieldResolverInterface;
use Risecommerce\McpOrderTools\Model\FieldResolver\CastsScalars;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;

/**
 * Status-history identity + metadata.
 */
class IdentityResolver implements OrderCommentFieldResolverInterface
{
    use CastsScalars;

    public const KEY = 'identity';

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 10;
    }

    /** @inheritDoc */
    public function resolve(OrderStatusHistoryInterface $comment, array $args): array
    {
        return [
            'entity_id' => (int) $comment->getEntityId(),
            'parent_id' => (int) $comment->getParentId(),
            'status' => $this->asString($comment->getStatus()),
            'is_visible_on_front' => (bool) $comment->getIsVisibleOnFront(),
            'is_customer_notified' => (bool) $comment->getIsCustomerNotified(),
            'entity_name' => $this->asString($comment->getEntityName()),
        ];
    }
}
