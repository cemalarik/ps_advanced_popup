# PrestaShop Advanced Smart Popup Module

**Version:** 1.0.0  
**Compatibility:** PrestaShop 1.7.0.0 - 8.x  
**License:** Academic Free License (AFL 3.0)

## Description

Create targeted, high-converting popups with smart triggers for your PrestaShop store. Perfect for newsletter subscriptions, promotional campaigns, exit-intent offers, and more.

## Features

- ✅ Multiple popup types (HTML, Image, Newsletter)
- ✅ Smart triggers (Page Load, Exit Intent, Scroll, Inactivity)
- ✅ Advanced targeting (Pages, Customer Groups, Devices)
- ✅ Frequency control with cookie management
- ✅ Beautiful animations (Animate.css integration)
- ✅ Built-in newsletter subscription with AJAX
- ✅ Statistics dashboard (Impressions, Conversions)
- ✅ Multi-language support
- ✅ Responsive design (Mobile-friendly)
- ✅ No core file modifications
- ✅ SEO-friendly (Google mobile guidelines compliant)

## Installation

1. Download the module ZIP file
2. Go to **Back Office > Modules > Module Manager**
3. Click **Upload a module**
4. Select the ZIP file
5. Click **Configure** to create your first popup

## Quick Start

1. Navigate to **Modules > Smart Popup**
2. Click **Add New Popup**
3. Fill in the basic settings:
   - Title (internal reference)
   - Popup Type (HTML/Image/Newsletter)
   - Content
4. Configure trigger settings
5. Set targeting rules
6. Save and activate

## Popup Types

| Type | Description | Best For |
|------|-------------|----------|
| **HTML Content** | Rich text with TinyMCE editor | Announcements, promotions |
| **Image Only** | Single clickable image | Visual campaigns, banners |
| **Newsletter** | Email subscription form | Lead generation |

## Trigger Types

| Trigger | Description | Value |
|---------|-------------|-------|
| **On Page Load** | Shows after X seconds | Seconds (e.g., 5) |
| **Exit Intent** | Mouse leaves viewport (Desktop) | Not required |
| **Scroll Percentage** | User scrolls X% of page | Percentage (e.g., 50) |
| **Inactivity** | No user activity for X seconds | Seconds (e.g., 30) |

## Targeting Options

### Page Targeting
- All Pages
- Homepage only
- Category Pages
- Product Pages
- Cart
- Checkout

### Customer Group Targeting
- Visitors (Guest)
- Registered Customers
- Custom Groups (VIP, Wholesale, etc.)

### Device Targeting
- Desktop
- Tablet
- Mobile

## Configuration

### General Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Active | Enable/disable popup | No |
| Title | Internal identification | - |
| Popup Type | HTML/Image/Newsletter | HTML |
| Start Date | When popup becomes active | Immediate |
| End Date | When popup expires | Never |
| Priority | Higher = shown first | 0 |

### Design Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Content | HTML content (TinyMCE) | - |
| CTA Button Text | Call-to-action text | - |
| CTA Button URL | Button link | - |
| Width | Popup width in pixels | 600px |
| Background Color | Hex color code | #ffffff |
| Background Image | Optional image | - |
| Border Radius | Corner roundness | 8px |
| Overlay Opacity | Background darkness (0-1) | 0.5 |
| Animation | Entry animation | fadeIn |
| Close Button Style | X button appearance | Default |
| Hide on Mobile | Don't show on phones | No |

### Frequency Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Show Again After | Days before reshowing | 1 day |

## Animations

Available animations (Animate.css):
- Fade In
- Fade In Down
- Fade In Up
- Bounce In
- Zoom In
- Slide In Down
- Slide In Up

## Statistics

Track popup performance in the admin panel:

- **Impressions**: Number of times popup was shown
- **Conversions**: Newsletter signups or CTA clicks
- **Conversion Rate**: Percentage of conversions
- **Daily/Weekly/Monthly** breakdowns
- **Visual charts** for trend analysis

## Hooks Used

| Hook | Purpose |
|------|---------|
| `displayHeader` | Load CSS/JS assets |
| `displayFooter` | Render popup HTML |
| `actionNewsletterRegistrationAfter` | Newsletter integration |

## Database Tables

```sql
ps_smart_popup           -- Main popup settings
ps_smart_popup_lang      -- Multi-language content
ps_smart_popup_targeting -- Targeting rules
ps_smart_popup_stats     -- Statistics data
```

## AJAX Endpoints

The module provides AJAX endpoints for:
- Newsletter subscription
- Statistics tracking (impressions/conversions)

## Cookie Management

- Cookie name: `smart_popup_{id}`
- Set when user closes popup
- Duration: Configurable (default 1 day)
- Permanent cookie on newsletter conversion

## Mobile Optimization

Following Google's mobile popup guidelines:
- Popup doesn't cover entire screen on mobile
- Easy-to-tap close button (44x44px minimum)
- Option to hide on mobile devices
- Bottom-aligned on small screens

## Performance

- CSS/JS loaded only when popups exist
- Client-side rule evaluation (no extra AJAX)
- Popup data cached server-side
- Async/defer script loading
- Minimal impact on LCP/CLS metrics

## File Structure

```
ps_advanced_popup/
├── ps_advanced_popup.php      # Main module file
├── config.xml                  # Module configuration
├── composer.json               # Composer autoload
├── index.php                   # Security file
├── logo.png                    # Module logo
├── logo.svg                    # SVG logo
├── README.md                   # This file
├── CHANGELOG.md                # Version history
├── LICENSE.md                  # AFL 3.0 license
│
├── classes/
│   ├── SmartPopup.php          # ObjectModel
│   └── SmartPopupValidator.php # Validation helper
│
├── controllers/
│   ├── admin/
│   │   └── AdminSmartPopupController.php
│   └── front/
│       └── ajax.php            # AJAX handler
│
├── sql/
│   ├── install.sql
│   └── uninstall.sql
│
├── translations/
│   ├── en.php
│   └── tr.php
│
├── upgrade/
│   └── upgrade-1.0.1.php
│
├── vendor/
│   └── autoload.php
│
├── views/
│   ├── css/
│   │   ├── front.css           # Frontend styles
│   │   ├── back.css            # Admin styles
│   │   └── animate.min.css     # Animations
│   ├── js/
│   │   ├── front.js            # Frontend logic
│   │   └── back.js             # Admin logic
│   └── templates/
│       ├── admin/
│       │   └── stats.tpl       # Statistics page
│       └── hook/
│           └── popup.tpl       # Popup template
│
└── docs/
    ├── SRS.md                  # Requirements spec
    ├── DEVELOPMENT_OVERVIEW.md
    ├── PHASE_1.md
    ├── PHASE_2.md
    ├── PHASE_3.md
    └── PHASE_4.md
```

## Customization

### Custom Styling

Override styles in your theme:

```css
/* In your theme's custom.css */
.smart-popup-container {
    /* Your custom styles */
}

.smart-popup-close {
    /* Custom close button */
}
```

### Custom Templates

Copy templates to your theme folder:
```
themes/your-theme/modules/ps_advanced_popup/views/templates/hook/popup.tpl
```

## Troubleshooting

### Popup not showing
1. Check if popup is active
2. Verify date range (start/end dates)
3. Check targeting rules match current page
4. Clear browser cookies
5. Check browser console for JS errors

### Newsletter not working
1. Verify AJAX URL is accessible
2. Check if ps_emailsubscription module is installed
3. Review browser network tab for errors

### Statistics not recording
1. Ensure AJAX endpoint is accessible
2. Check for JavaScript errors
3. Verify database tables exist

## Uninstallation

1. Go to **Back Office > Modules > Module Manager**
2. Find "Advanced Smart Popup"
3. Click **Uninstall**

**Warning:** Uninstalling will delete all popup data and statistics.

## Support

For issues or feature requests, please contact the developer.

## License

This module is licensed under the Academic Free License (AFL 3.0).
See LICENSE.md for full terms.
