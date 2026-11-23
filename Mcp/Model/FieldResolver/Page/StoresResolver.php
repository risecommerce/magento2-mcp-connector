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
use Magento\Cms\Model\Page as PageModel;

/**
 * `stores` slice: store_ids[]. `[0]` = "All Store Views".
 *
 * The association isn't on PageInterface, so we read it from the concrete
 * {@see PageModel}.
 */
class StoresResolver implements PageFieldResolverInterface
{
    public const KEY = 'stores';

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
        return 60;
    }

    /**
     * @inheritDoc
     */
    public function resolve(PageInterface $page, array $args): array
    {
        unset($args);
        $storeIds = [];
        if ($page instanceof PageModel) {
            $raw = $page->getStores();
            if (is_array($raw)) {
                foreach ($raw as $storeId) {
                    if (is_numeric($storeId)) {
                        $storeIds[] = (int) $storeId;
                    }
                }
            }
        }
        return [
            'store_ids' => array_values(array_unique($storeIds)),
        ];
    }
}
