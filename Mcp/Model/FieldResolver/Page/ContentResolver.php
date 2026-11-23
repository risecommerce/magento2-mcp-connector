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
 * `content` slice: content_heading, content (HTML body).
 */
class ContentResolver implements PageFieldResolverInterface
{
    public const KEY = 'content';

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
        return 30;
    }

    /**
     * @inheritDoc
     */
    public function resolve(PageInterface $page, array $args): array
    {
        unset($args);
        return [
            'content_heading' => $page->getContentHeading() !== null
                ? (string) $page->getContentHeading()
                : null,
            'content' => $page->getContent() !== null
                ? (string) $page->getContent()
                : null,
        ];
    }
}
