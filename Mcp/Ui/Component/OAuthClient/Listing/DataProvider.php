<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Ui\Component\OAuthClient\Listing;

use Risecommerce\Mcp\Model\OAuth\ResourceModel\Client\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Admin grid data source for the OAuth client listing. Wires the Client
 * collection into the UI component framework so the grid can paginate,
 * filter and sort against the `risecommerce_mcp_oauth_client` table.
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @phpstan-param array<string, mixed> $meta
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @phpstan-return array{totalRecords: int, items: array<int, array<string, mixed>>}
     */
    public function getData(): array
    {
        $items = [];
        foreach ($this->collection->getItems() as $item) {
            $row = $item->getData();
            if (!is_array($row)) {
                continue;
            }
            /** @var array<string, mixed> $row */
            $items[] = $row;
        }
        return [
            'totalRecords' => (int) $this->collection->getSize(),
            'items' => $items,
        ];
    }
}
