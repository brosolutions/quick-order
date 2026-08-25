# Changelog

## 1.1.2 - 2026-08-25

### Fixed
- Cart icon count and cart totals no longer stay stale after adding products from Quick Order - the request went through `fetch()` instead of jQuery's AJAX, so the `cart` customer-data section was never invalidated. It's now invalidated explicitly before the redirect to the cart page.
- CSV import no longer silently swaps in a default product variant when a row's `option_N` value doesn't match any real option on a configurable product (e.g. a typo'd color or size). That row is now skipped and reported as an error instead of adding the wrong variant.

### Added
- CSV import now shows an import summary (products imported, rows skipped with the reason) in the page's standard message area instead of a status line next to the upload button that was easy to miss.

## 1.1.1 — 2026-08-04

### Fixed
- `magento/framework`, `magento/module-catalog`, `magento/module-store`, `magento/module-eav` now declare an explicit minimum version instead of `*`, which Magento Marketplace's composer.json validator rejects.

## 1.1.0 — 2026-07-21

### Added
- CSV import/export for the quick order product list — download the current list as a `.csv` file, or upload a `.csv` to populate the form (`sku`, `qty`, and `option_N` columns for configurable/bundle/grouped products).
- `LinksProviderInterface` — an extension point that lets third-party modules supply alternative products for items that are no longer available. When a provider returns alternatives, they're shown under the item in the list and "Add to cart" is blocked until it's replaced. This module ships the hook only, with no provider wired in by default — a separate connector module (pairing with SalesUp's replacement-product data) is planned to make this functional out of the box.
- Unit test coverage for `AddToCart`, `Upload` controllers and the `ConvertCurrency`, `GetCurrencySymbol`, `GetQuickOrderEnable`, `GetSearchResultsLimit`, `GetStoreCurrency`, `GetStoreId` services.

### Changed
- `ProductManagementInterface::getProduct()` now accepts an array of SKUs in addition to a single SKU string, to support CSV import of multiple products at once.

## 1.0.0 — 2025-08-13

- Initial release: search products by SKU or name and add multiple items to the cart at once.
