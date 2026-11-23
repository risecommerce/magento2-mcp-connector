# Risecommerce_McpInventoryTools

Inventory-domain MCP tools for [`Risecommerce_Mcp`](https://github.com/risecommerce/magento2-mcp-module). Exposes Magento's Multi-Source Inventory (MSI) — sources, stocks, per-source quantities, salable quantity, and bulk inventory moves — as MCP tools.

## Requirements

- `Risecommerce_Mcp`
- `Risecommerce_McpCatalogTools` **1.2 or newer** — this module implements two of its
  contracts: the `salable_qty` slice it adds to `catalog.product.get` /
  `catalog.product.list`, and the MSI-backed single-source check that makes
  `catalog.product.stock.set` warn on multi-source stores. Composer installs it
  automatically.
- Magento MSI (`Magento_InventoryApi`, `Magento_InventorySalesApi`, `Magento_InventoryCatalogApi`, `Magento_InventoryConfigurationApi`, `Magento_InventoryReservationsApi`) — shipped with Magento Open Source 2.3+.

If a store has MSI removed, do not enable this module; legacy single-stock writes are covered by `catalog.product.stock.set` in `Risecommerce_McpCatalogTools`.

## Install

```bash
composer require risecommerce/magento2-mcp-inventory-tools
bin/magento module:enable Risecommerce_McpInventoryTools
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Concepts

Three things, easy to conflate:

| Term | What it is |
|---|---|
| **Source** | A physical place stock lives — warehouse, retail store, drop-shipper. Identified by `source_code`. |
| **Stock** | An aggregation of sources, assigned to one or more websites. A website sells from exactly one stock. |
| **Source item** | The quantity of one SKU at one source. This is the row you edit day to day. |

**On-hand vs salable.** `inventory.source_item.list` reports on-hand quantity per source. `inventory.salable_qty.get` reports what a customer can actually buy — on-hand minus reservations held by orders that have not shipped. The `reserved` field is the difference, which is the answer to "why is salable lower than what's in the warehouse".

## Read tools

| Tool | What it does |
|---|---|
| `inventory.source.list` | Paginated sources; filter by `enabled`, `country_id`. Use it to discover valid `source_code` values. |
| `inventory.source.get` | One source by `source_code`, with address and contact details. |
| `inventory.stock.list` | Paginated stocks, each with its linked sources (and priorities) and assigned sales channels. |
| `inventory.stock.get` | One stock by `stock_id`, or by `website_code` to find the stock serving a website. Defaults to the default stock. |
| `inventory.source_item.list` | Per-source quantities. Filter by `sku` (a list is resolved in one query), `source_code`, `status`. |
| `inventory.salable_qty.get` | Salable quantity, `is_salable`, and the reservation delta for up to 100 SKUs. Scope with `stock_id` or `website_code`. |
| `inventory.stock_item_configuration.get` | Per (SKU, stock) settings: backorders, min qty, notification threshold, sale limits, and which are inherited from global config. |

Installing this module also adds a `salable_qty` slice to `catalog.product.get` / `catalog.product.list` in `Risecommerce_McpCatalogTools`. Drop it with `exclude: ["salable_qty"]`.

## Write tools

All write tools require `risecommerce_mcp/general/allow_writes = 1` **and** `allow_writes = 1` on the calling token, and all set `confirmationRequired`.

| Tool | What it does |
|---|---|
| `inventory.source_item.set` | **The main inventory write.** Sets quantity and status for up to 500 (SKU, source) pairs in one batched save. Reports per-item saved/skipped. Composite products are skipped with an explanation. |
| `inventory.source_item.delete` | Unassigns (SKU, source) pairs. Different from zeroing quantity — the row is removed. Non-existent pairs are skipped, not fatal. |
| `inventory.stock_item_configuration.set` | PATCH-style per (SKU, stock) settings. Setting a value clears its `use_config_*` flag; pass `use_config_*: true` to hand it back to the global default. |
| `inventory.source.create` | Create a source. It holds no stock and serves no website until linked to a stock. |
| `inventory.source.update` | PATCH-style. Magento has **no source delete** — retire one with `enabled: false`. |
| `inventory.stock.create` | Create a stock. Sells nothing until sources and sales channels are attached. |
| `inventory.stock.update` | Rename a stock. |
| `inventory.stock.delete` | Delete a stock. Refuses the default stock, and refuses a stock that still has websites assigned. |
| `inventory.stock.assign_sources` | Link sources to a stock with a shipping `priority` (lower runs first). Re-assigning updates the priority. |
| `inventory.stock.unassign_sources` | Unlink sources. Source items and quantities are untouched. |
| `inventory.stock.set_sales_channels` | **Replaces** the whole website assignment set — anything omitted falls back to the default stock. Pass the full intended list. |

### Bulk tools

These touch thousands of rows with no per-item reporting. Each has its own ACL so it can be withheld from a role that otherwise manages inventory.

| Tool | What it does |
|---|---|
| `inventory.bulk.source_assign` | Assign every listed SKU to every listed source, creating zero-quantity source items. Standing up a new warehouse. |
| `inventory.bulk.source_unassign` | **Destructive.** Deletes those source items and their quantities outright. No undo. |
| `inventory.bulk.transfer` | Moves full quantities between two sources. `unassign_from_origin: true` also deletes the origin rows — closing a warehouse. |
| `inventory.bulk.partial_transfer` | Moves a specified quantity per SKU. The origin keeps the remainder and stays assigned. |

Bulk lists are capped at 1000 SKUs and 50 sources per call.

## ACL

Every tool has its own resource under `Risecommerce_Mcp::tools`, named `Risecommerce_McpInventoryTools::tool_<tool_name_with_underscores>`. Write tools also enforce the matching Magento admin resource (`Magento_InventoryApi::source_edit`, `::stock_edit`, `::stock_delete`, `::stock_source_link`, `::stock_source_item_assign`), so MCP can never do what the admin UI cannot.

## Extending

See [`docs/EXTENDING.md`](docs/EXTENDING.md).
