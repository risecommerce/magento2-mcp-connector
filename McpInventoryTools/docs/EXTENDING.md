# Extending `Risecommerce_McpInventoryTools`

## Add a field to the product `salable_qty` slice

This module contributes a `salable_qty` resolver to `Risecommerce_Mcp` (catalog tools)'
product read tools. To add your own slice — a per-source breakdown, an ERP
reference, a lead time — implement the catalog module's
`ProductFieldResolverInterface` and register it the same way.

```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Mcp\Resolver;

use Risecommerce\Mcp\Api\ProductFieldResolverInterface;
use Magento\Catalog\Api\Data\ProductInterface;

final class LeadTimeResolver implements ProductFieldResolverInterface
{
    public function getKey(): string { return 'lead_time'; }
    public function getSortOrder(): int { return 42; }

    public function resolve(ProductInterface $product, array $args): array
    {
        return ['lead_time' => ['days' => 5]];
    }
}
```

```xml
<type name="Risecommerce\Mcp\Tool\Catalog\Product\ProductGet">
    <arguments>
        <argument name="fieldResolvers" xsi:type="array">
            <item name="lead_time" xsi:type="object">
                Vendor\Module\Mcp\Resolver\LeadTimeResolver
            </item>
        </argument>
    </arguments>
</type>
```

DI array items merge across modules, so the built-in resolvers stay in place.
Resolvers must not throw — a source of truth that is unavailable should degrade
to `null`, the way `SalableQtyResolver` does for composite products.

## Replace the single-source check

`catalog.product.stock.set` warns when a legacy stock write would silently land
on the default source. It asks
`Risecommerce\Mcp\Api\SingleSourceModeCheckerInterface`, which this
module preferences to an MSI-backed implementation. Override the preference if
your store decides multi-source differently:

```xml
<preference for="Risecommerce\Mcp\Api\SingleSourceModeCheckerInterface"
            type="Vendor\Module\Model\MySingleSourceModeChecker"/>
```

## Add a tool

Follow the core module's `docs/EXTENDING.md`. Two rules specific to inventory:

1. **Guard composite products.** Configurable, bundle and grouped SKUs carry no
   source items. Inject
   `Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface`
   and report a per-item skip instead of letting MSI throw.
2. **Batch the save.** `SourceItemsSaveInterface` and the stock-source-link
   services take arrays. Building the full list and saving once avoids a reindex
   per row.

## Write-tool ACL layering

Every write tool in this module implements both `ToolInterface` and
`Risecommerce\Mcp\Api\UnderlyingAclAwareInterface`. The handler enforces two ACL
checks per call:

1. The tool's MCP-scoped resource — e.g.
   `Risecommerce_McpInventoryTools::tool_inventory_source_item_set`.
2. The underlying Magento resource from `getUnderlyingAclResource()` — e.g.
   `Magento_InventoryApi::stock_source_item_assign`.

Both must pass. "MCP cannot do what the admin UI cannot."

## Run setup

```
bin/magento setup:upgrade
bin/magento setup:di:compile
```
