# Change Log

## 1.2.0 - 2026-02-14

### Added

- Added 'Make Assets Searchable' utility to bulk set assets as searchable.

## 1.1.9 - 2026-02-11

### Fixed

- Fixed SearchQuery class adding an empty term when parsing in certain situations.
- Fixed SearchQueryTerm class returning empty permutations in certain situations.

### Changed

- PDF parser will now use an assets file path instead of the its URL if using a Local Filesystem.

## 1.1.8 - 2025-12-16

### Fixed

- Added null fallback for asset titles.

### Changed

- Asset search data now includes title and alt text.

## 1.1.7 - 2025-12-10

### Fixed

- Fixed issue with result description parsing.

## 1.1.6 - 2025-10-09

### Fixed

- Fixed typo in TrackHitEvent class name.

## 1.1.5 - 2025-10-09

### Fixed

- Fixed issue where only the first 250 pages or assets would get indexed.

## 1.1.4 - 2025-10-09

### Added

- Added hit tracking event.

### Fixed

- Fixed issue where action urls with %2F would get tracked.

## 1.1.3 - 2025-10-08

### Fixed

- Fixed issue that could prevent assets from being indexed when multiple volumes are being used.

## 1.1.2 - 2025-10-07

### Updated

- Updated to address deprecation issues in PHP 8.4.

### Fixed

- Fixed issue that would prevent all section entries from being indexed.
- Fixed result search and sitemap switches getting improperly enabled after running utility update.
- Fixed result error state getting improperly reset when running utility update.
- Fixed result error date not always being set.

## 1.1.1 - 2025-04-30

### Fixed

- Fixed Searchable toggle starting off toggled on.
- Fixed UrlCleaner failing on malformed URLs.
- Fixed issue where invalid utf8 multibyte sequences could cause errors.
- Fixed missing null check after querying ResultElement.
- Fixed empty PDFs causing errors during indexing.

## 1.1.0 - 2024-11-13

### Added

- Added PageResult::EVENT\_UPDATE\_MAIN\_DATA event to allow custom indexing of pages.
- Added AssetResult::EVENT\_UPDATE\_MAIN\_DATA event to allow custom indexing of assets.
- Added option to force update pages and assets so that custom implementations can be run on pages and assets that have not changed.

### Fixed

- Fixed issue with searchable asset field value not holding in certain situations.

## 1.0.0 - 2024-07-27

Initial release.
