<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Page;

use Risecommerce\Mcp\Api\PageFieldResolverInterface;
use Magento\Cms\Api\Data\PageInterface;

/**
 * `identity` slice: page_id, identifier, title.
 */
class IdentityResolver implements PageFieldResolverInterface
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
    public function resolve(PageInterface $page, array $args): array
    {
        unset($args);
        return [
            'page_id' => (int) $page->getId(),
            'identifier' => (string) $page->getIdentifier(),
            'title' => (string) $page->getTitle(),
        ];
    }
}
