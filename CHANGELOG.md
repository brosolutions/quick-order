# Changelog

## 1.1.0 — 2026-07-21

### Added
- CSV import/export for the quick order product list — download the current list as a `.csv` file, or upload a `.csv` to populate the form (`sku`, `qty`, and `option_N` columns for configurable/bundle/grouped products).
- `LinksProviderInterface` — an extension point that lets third-party modules supply alternative products for items that are no longer available. When a provider returns alternatives, they're shown under the item in the list and "Add to cart" is blocked until it's replaced. This module ships the hook only; no built-in provider or admin configuration is included, so alternatives are shown only if another installed module implements the interface.
- Unit test coverage for `AddToCart`, `Upload` controllers and the `ConvertCurrency`, `GetCurrencySymbol`, `GetQuickOrderEnable`, `GetSearchResultsLimit`, `GetStoreCurrency`, `GetStoreId` services.

### Changed
- `ProductManagementInterface::getProduct()` now accepts an array of SKUs in addition to a single SKU string, to support CSV import of multiple products at once.

## 1.0.0 — 2025-08-13

- Initial release: search products by SKU or name and add multiple items to the cart at once.
