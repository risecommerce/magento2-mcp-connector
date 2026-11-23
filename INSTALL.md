# Risecommerce MCP — app/code installation

Copy the `Risecommerce` directory into the Magento installation's `app/code` directory so the modules are located at `app/code/Risecommerce/*`.

From the Magento project root, run:

```bash
bin/magento module:enable \\
  Risecommerce_Core \\
  Risecommerce_PiiRedactor \\
  Risecommerce_Mcp \\
  Risecommerce_McpCatalogTools \\
  Risecommerce_McpCmsTools \\
  Risecommerce_McpCustomerTools \\
  Risecommerce_McpInventoryTools \\
  Risecommerce_McpMarketingTools \\
  Risecommerce_McpOrderTools \\
  Risecommerce_McpReportTools \\
  Risecommerce_McpTaxTools
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The PII redactor is bundled as `Risecommerce_PiiRedactor`, so this app/code package does not require a separate Composer vendor package for that library. Review the module README and configure MCP credentials before connecting a client.
