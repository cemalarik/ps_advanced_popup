# Changelog

## 2.0.1

- Added a real 1.x -> 2.x database migration (`upgrade-2.0.0.php`); upgrading no longer requires uninstall/reinstall.
- Fixed SQL escaping when duplicating popups (apostrophes in content broke the copy).
- Kept variant IDs stable on save so A/B assignments and historical variant stats survive edits.
- Used `navigator.sendBeacon` for CTA click / close tracking so events are not lost during navigation.
- Enforced `isCleanHtml` / URL validation on all admin text fields (ObjectModel validation was bypassed by raw SQL writes).
- Validated uploaded images by content (`getimagesize`) and size, not just extension.
- Whitelisted device/page type and validated session key and coupon code lengths in the AJAX endpoint.
- Newsletter popup now updates the `newsletter` flag of existing customers instead of writing them into the guest table.
- The configured success message is now shown after newsletter signup.
- Rebuilt `en.php` / `tr.php` with proper md5 translation keys (translations were never loaded before).
- Added `logo.png`, `views/img/index.php`; removed test artifacts from the package.
- Added 90-day retention cleanup for raw event logs.

## 2.0.0

- Rebranded the module as Advanced Popup Studio.
- Replaced the HelperForm admin flow with a custom dashboard and guided editor.
- Added campaign presets, live preview, A/B variants and richer targeting rules.
- Rebuilt the front popup as an accessible dialog with focus management and mobile bottom sheet behavior.
- Added coupon copy, CTA click, newsletter outcome and close tracking.
- Replaced aggregate-only stats with anonymous event logging and daily summaries.
- Removed CDN chart dependency and unused animation asset.
- Updated fresh-install database schema. Existing 1.x data migration is not included.

## 1.0.0

- Initial popup module with basic popup types, triggers, targeting and statistics.
