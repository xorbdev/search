# Change Log

## 1.1.1 - 2025-04-30

### Fixed

- Fixed Searchable toggle starting off checked.
- Fixed UrlCleaner failing on malformed URLs.
- Fixed issue where invalid utf8 multibyte sequences could cause errors.
- Fixed missing null check after querying ResultElement.
- Fixed empty PDFs causing during indexing errors.

## 1.1.0 - 2024-11-13

### Added

- Added PageResult::EVENT\_UPDATE\_MAIN\_DATA event to allow custom indexing of pages.
- Added AssetResult::EVENT\_UPDATE\_MAIN\_DATA event to allow custom indexing of assets.
- Added option to force update pages and assets so that custom implementations can be run on pages and assets that have not changed.

### Fixed

- Fixed issue with searchable asset field value not holding in certain situations.

## 1.0.0 - 2024-07-27

Initial release.
