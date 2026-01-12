# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-08

### Added
- Initial release
- Multiple popup types: HTML Content, Image Only, Newsletter Form
- Smart triggers: On Page Load, Exit Intent, Scroll Percentage, User Inactivity
- Advanced targeting: Pages, Customer Groups, Devices
- Frequency control with cookie-based management
- Animation library integration (Animate.css subset)
- Built-in newsletter subscription with AJAX
- Statistics dashboard with charts
- Impression and conversion tracking
- Multi-language support (English, Turkish)
- Responsive design with mobile optimization
- Admin panel with tabbed form interface
- TinyMCE editor integration for HTML content
- Background image upload support
- Customizable close button styles
- Overlay opacity control
- Priority-based popup display
- Date range scheduling (start/end dates)

### Security
- All inputs validated with PrestaShop methods
- XSS protection on HTML content
- SQL injection prevention with pSQL/bqSQL
- CSRF protection on admin actions
- Rate limiting on AJAX endpoints
- No core file modifications

### Performance
- Client-side rule evaluation
- Server-side caching
- Lazy loading of assets
- Minimal DOM footprint
