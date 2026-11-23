<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCmsTools\Model\FieldResolver\Page;

use Risecommerce\McpCmsTools\Api\PageFieldResolverInterface;
use Magento\Cms\Api\Data\PageInterface;

/**
 * `timestamps` slice: creation_time, update_time.
 */
class TimestampsResolver implements PageFieldResolverInterface
{
    public const KEY = 'timestamps';

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
        return 90;
    }

    /**
     * @inheritDoc
     */
    public function resolve(PageInterface $page, array $args): array
    {
        unset($args);
        return [
            'creation_time' => $page->getCreationTime() !== null
                ? (string) $page->getCreationTime()
                : null,
            'update_time' => $page->getUpdateTime() !== null
                ? (string) $page->getUpdateTime()
                : null,
        ];
    }
}
