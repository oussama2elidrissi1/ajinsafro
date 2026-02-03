# Changelog - Ajinsafro Core Plugin

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-02-03

### Added - Initial Release

#### Core Features
- ✅ Admin settings page with configuration options
- ✅ Package Builder shortcode `[aj_package_builder]`
- ✅ AJAX endpoints for package state, actions, and checkout
- ✅ REST API endpoint for Laravel to WordPress sync
- ✅ Automatic image import from Laravel
- ✅ TravelerWP custom table integration
- ✅ HMAC-SHA256 signature verification for security
- ✅ Rate limiting (30 requests/minute per IP)
- ✅ Transient caching system
- ✅ Sync logging to `wp-content/uploads/ajinsafro-sync.log`

#### Settings Options
- Laravel Base URL configuration
- Checkout Base URL configuration
- HMAC Secret for sync security
- Enable/Disable sync toggle
- Cache TTL configuration (seconds)
- **Auto-inject Package Builder** (checkbox)
- **Auto-inject Position** (before/after content)

#### Frontend Display
- Responsive Package Builder UI
- Day-by-day navigation
- Items display with icons and badges
- Real-time pricing display
- Book Now button with checkout integration
- Smooth tab switching
- Loading states and error handling

#### Sync Capabilities
- Upsert WordPress tours from Laravel
- Delete tours from WordPress
- Automatic slug generation
- Featured image + gallery import
- Post meta synchronization
- Custom table `{prefix}_st_tours` updates
- Deduplication of imported images

#### Auto-Injection System
- Automatic Package Builder display on tour pages
- Configurable position (before/after content)
- Anti-duplication protection
- Smart detection of manual shortcode usage
- Conditional injection based on Laravel ID presence

#### Developer Features
- PSR-4 autoloading
- Clean OOP architecture
- Separation of concerns
- No external dependencies
- WordPress coding standards compliant
- PHP 8.0+ compatibility

### Security
- Nonce verification for all AJAX requests
- HMAC signature verification for sync endpoint
- Input sanitization and output escaping
- Capability checks (manage_options)
- SQL injection protection
- XSS protection
- Rate limiting

### Performance
- Transient caching for package states
- Conditional asset loading
- Optimized database queries
- Image deduplication
- Minimal hook usage

---

## File Structure

```
ajinsafro-core/
├── ajinsafro-core.php                    # Plugin bootstrap
├── README.md                              # Documentation
├── CHANGELOG.md                           # This file
├── includes/
│   ├── Admin/
│   │   └── Settings.php                   # Admin settings page
│   ├── Ajax/
│   │   └── Handler.php                    # AJAX request handlers
│   ├── Core/
│   │   ├── Assets.php                     # CSS/JS enqueue
│   │   └── Options.php                    # Options management
│   ├── Frontend/
│   │   ├── AutoInjector.php              # Auto-injection system
│   │   └── Shortcode.php                  # Shortcode handler
│   └── Sync/
│       ├── RestEndpoint.php               # REST API endpoint
│       └── TourSyncer.php                 # Tour sync logic
├── assets/
│   ├── css/
│   │   ├── admin.css                      # Admin styles
│   │   └── package-builder.css            # Frontend styles
│   └── js/
│       └── package-builder.js             # Frontend JavaScript
└── templates/
    ├── admin/
    │   └── settings.php                   # Settings page template
    └── frontend/
        └── package-builder.php            # Package builder template
```

---

## WordPress Compatibility

- **Minimum WordPress version:** 6.0
- **Tested up to:** 6.4
- **Minimum PHP version:** 8.0
- **Required theme:** TravelerWP (with `st_tours` post type)

---

## Installation

1. Copy `ajinsafro-core` folder to `wp-content/plugins/`
2. Activate plugin in WordPress Admin
3. Configure settings in **Ajinsafro Core** menu
4. Tours will automatically display Package Builder (if auto-inject enabled)

---

## Usage

### Automatic Display (Default)
- Package Builder appears automatically on all tour pages
- Configure position in settings (before/after content)

### Manual Display
- Disable auto-injection in settings
- Add `[aj_package_builder]` shortcode to tour content

### Laravel Sync
- Configure HMAC secret (must match Laravel)
- Enable sync in settings
- Use endpoint: `/wp-json/ajinsafro-sync/v1/laravel-to-wp`

---

## Known Issues

None at this time.

---

## Upgrade Notice

### 1.0.0
Initial release. No upgrade needed.

---

## Credits

**Developed by:** Ajinsafro Development Team  
**Date:** 2026-02-03  
**License:** Proprietary

---

## Support

For support, please contact the Ajinsafro development team or consult:
- `README.md` - Complete documentation
- `AUTO_INJECT_FEATURE_GUIDE.md` - Auto-injection feature guide
- Sync logs: `wp-content/uploads/ajinsafro-sync.log`
