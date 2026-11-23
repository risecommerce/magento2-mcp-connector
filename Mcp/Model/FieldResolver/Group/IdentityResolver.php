<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Group;

use Risecommerce\Mcp\Api\GroupFieldResolverInterface;
use Magento\Customer\Api\Data\GroupInterface;

class IdentityResolver implements GroupFieldResolverInterface
{
    public const KEY = 'identity';

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function resolve(GroupInterface $group, array $args): array
    {
        unset($args);
        return [
            'id' => $group->getId() !== null ? (int) $group->getId() : null,
            'code' => (string) $group->getCode(),
        ];
    }
}
