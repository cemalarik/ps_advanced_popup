# Advanced Popup Studio for PrestaShop

Advanced Popup Studio is a PrestaShop module for building targeted, accessible, conversion-focused popups from the back office. It includes campaign presets, A/B variants, coupon and newsletter flows, rule-based targeting, live preview, and anonymous event analytics.

> This is an independent open-source module. It is not an official PrestaShop product.

## Features

- Custom back-office dashboard with campaign status, date range, trigger, targeting summary, impressions, conversions, close rate, and conversion rate.
- Guided popup editor with steps for goal, content, design, targeting, preview, and publishing.
- Built-in campaign presets:
  - newsletter discount
  - exit-intent coupon
  - cart reminder
  - free shipping
  - product upsell
  - image campaign
  - announcement
- Desktop, tablet, and mobile preview in the editor.
- A/B variant support with traffic split and a default winner metric of `conversion_rate`.
- Targeting rules for page type, URL, device, customer group, login state, language, currency, cart total, cart products, and cart categories.
- Accessible front-office dialog with `role="dialog"`, `aria-modal`, focus trap, ESC close, focus restore, visible focus states, and reduced-motion support.
- Mobile bottom sheet behavior by default, with modal and hidden mobile options.
- Coupon flow with one-click copy, success state, and coupon copy tracking.
- Newsletter flow with submit, success, and error tracking.
- Anonymous event analytics for impressions, closes, CTA clicks, newsletter outcomes, coupon copies, and variant assignment.
- Local chart helper and module assets. No CDN dependency is required.

## Compatibility

| Requirement | Status |
| --- | --- |
| PrestaShop | Verified on `1.7.8.11`; designed for the legacy module runtime used by PrestaShop `1.7.8.x` and `8.x` |
| PHP | `>= 7.2` |
| Database | MySQL or MariaDB supported by PrestaShop |
| Browser | Modern browsers with progressive fallback for clipboard copy |

PrestaShop `8.x` should be tested in your target shop before production use. The main development and browser verification environment for this release was PrestaShop `1.7.8.11`.

## Fresh Install Notice

Version `2.0.0` uses a new fresh-install schema. It is not a migration-preserving upgrade from older `1.x` data.

On install, the module creates:

- `ps_smart_popup`
- `ps_smart_popup_lang`
- `ps_smart_popup_variant`
- `ps_smart_popup_variant_lang`
- `ps_smart_popup_targeting`
- `ps_smart_popup_event`
- `ps_smart_popup_stats_daily`
- `ps_smart_popup_subscriber`
- `ps_smart_popup_coupon_event`

On uninstall, these module tables are removed. Back up any production data before uninstalling.

## Installation

1. Copy or clone this repository into your PrestaShop modules directory as:

   ```bash
   modules/ps_advanced_popup
   ```

2. Install the module from the back office:

   ```text
   Modules > Module Manager > Advanced Popup Studio > Install
   ```

3. Open the module workspace:

   ```text
   Modules > Popup Studio
   ```

Optional CLI install:

```bash
php bin/console prestashop:module install ps_advanced_popup
```

If you are reinstalling during development, use PrestaShop's module reset command or uninstall/install again so the fresh schema is recreated.

## Admin Workflow

1. Open `Modules > Popup Studio`.
2. Choose a preset template or start a new campaign.
3. Fill localized content: title, subtitle, body, CTA, coupon, consent text, and success message.
4. Configure layout, colors, width, radius, overlay opacity, close behavior, animation, and mobile behavior.
5. Configure trigger and targeting rules.
6. Preview desktop, tablet, and mobile states.
7. Enable publishing and save.
8. Review anonymous performance analytics from the campaign stats page.

## Front-Office Behavior

Supported triggers:

- load delay
- exit intent
- scroll depth
- inactivity

Supported layouts:

- centered modal
- image top
- image left
- image right
- full image
- compact coupon
- newsletter

The front popup is rendered as an accessible modal dialog. On mobile, the default behavior is a bottom sheet that keeps the close button reachable and avoids taking over the entire viewport.

## Analytics and Privacy

The analytics model is intentionally lightweight and anonymous.

Tracked event types include:

- `impression`
- `close`
- `cta_click`
- `newsletter_submit`
- `newsletter_success`
- `newsletter_error`
- `coupon_copy`
- `variant_assignment`

The event table stores popup, variant, shop, language, device, page type, session key, URL hash, event type, timestamp, and optional event metadata. It does not store full visited URLs in the analytics table.

Newsletter submissions can store an email address in `ps_smart_popup_subscriber` when the visitor submits the form. Coupon copy events can store coupon mapping data in `ps_smart_popup_coupon_event`.

You are responsible for showing any cookie, tracking, newsletter, or marketing consent notices required in your jurisdiction.

## AJAX Endpoints

The module front controller supports these actions:

- `track_event`
- `newsletter_subscribe`
- `coupon_copy`
- `cta_click`
- `preview_render`

The endpoint is generated through PrestaShop's module link system and exposed to the front script through `smartPopupData.ajaxUrl`.

## Development

Useful checks:

```bash
# PHP syntax check from the PrestaShop root
find modules/ps_advanced_popup -name '*.php' -print0 | xargs -0 -n1 php -l

# JavaScript syntax checks from the module root
node --check views/js/front.js
node --check views/js/back.js
node --check views/js/vendor/chart-lite.js
```

For a clean development database, uninstall and reinstall the module or run a module reset in your local PrestaShop environment.

## Project Structure

```text
classes/                       Object model, validation, stats helpers
controllers/admin/             Custom back-office dashboard, editor, stats
controllers/front/             AJAX tracking, newsletter, coupon, CTA handlers
sql/                           Fresh install and uninstall schema
translations/                  Legacy module translations
views/css/                     Back-office and front-office styles
views/js/                      Admin wizard, front runtime, local chart helper
views/templates/admin/         Dashboard, editor, stats templates
views/templates/hook/          Front popup template
```

## Known Limitations

- Existing `1.x` popup data is not migrated automatically.
- The module is designed for PrestaShop's legacy module controller/template system, not a Symfony back-office page.
- A/B testing currently supports two variants per popup.
- Analytics are module-local and anonymous; they are not a replacement for a dedicated analytics platform.

## License

Released under the [AFL-3.0](LICENSE.md) license.
