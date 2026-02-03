# Ajinsafro Core WordPress Plugin

Package Builder integration for Laravel booking system with TravelerWP theme.

## Version
1.0.0

## Requirements
- WordPress 6.0+
- PHP 8.0+
- TravelerWP theme (with `st_tours` post type)
- Laravel booking system (API endpoints)

## Features

### 1. Admin Settings Page
- Laravel API base URL configuration
- Checkout URL configuration
- HMAC secret for secure sync
- Enable/disable sync functionality
- Cache TTL configuration

### 2. Package Builder Shortcode
- Shortcode: `[aj_package_builder]`
- Displays interactive package builder on tour pages
- Day-by-day itinerary with items
- Real-time pricing display
- Book now functionality

### 3. AJAX Endpoints
- `aj_package_state` - Get package state from Laravel API
- `aj_package_action` - Perform actions (add/remove/modify items)
- `aj_create_checkout` - Create checkout token and redirect

### 4. Sync REST API
- Endpoint: `/wp-json/ajinsafro-sync/v1/laravel-to-wp`
- Sync tours from Laravel to WordPress
- HMAC signature verification
- Automatic image import
- Custom table updates

## Installation

### Step 1: Copy Plugin
```bash
# Copy the entire ajinsafro-core folder to WordPress plugins directory
cp -r wp-plugin/ajinsafro-core /path/to/wordpress/wp-content/plugins/
```

### Step 2: Activate Plugin
1. Log in to WordPress Admin
2. Go to **Plugins > Installed Plugins**
3. Find "Ajinsafro Core"
4. Click **Activate**

### Step 3: Configure Settings
1. Go to **Ajinsafro Core** menu in WordPress Admin
2. Configure settings:
   - **Laravel Base URL**: `https://booking.ajinsafro.net`
   - **Checkout Base URL**: `https://booking.ajinsafro.net`
   - **HMAC Secret**: Your shared secret key (must match Laravel)
   - **Enable Sync**: Check to enable sync endpoint
   - **Cache TTL**: 300 seconds (default)
3. Click **Save Settings**

### Step 4: Add Shortcode to Tour Template
Edit your TravelerWP child theme's single tour template (`single-st_tours.php`) and add:

```php
<?php echo do_shortcode('[aj_package_builder]'); ?>
```

Or add via page builder/editor.

## Usage

### Frontend (Tour Page)
Once configured, visitors can:
- View day-by-day itinerary
- See included items and optional add-ons
- View real-time pricing
- Click "Book Now" to proceed to checkout

### Admin (Sync from Laravel)
From Laravel, send POST request to sync endpoint:

```bash
POST /wp-json/ajinsafro-sync/v1/laravel-to-wp
Headers:
  Content-Type: application/json
  X-AJ-Signature: [HMAC-SHA256 signature]

Body:
{
  "action": "upsert",
  "entity_type": "tour",
  "laravel_id": 1,
  "slug": "sejour-dubai-7j-6n",
  "title": "Séjour Dubai 7 jours / 6 nuits",
  "content_html": "<p>Description...</p>",
  "address": "Dubai, UAE",
  "duration_day": "7 jours / 6 nuits",
  "adult_price": 10900,
  "child_price": 0,
  "is_featured": "off",
  "images": {
    "featured": "https://booking.ajinsafro.net/storage/image.jpg",
    "gallery": ["https://...1.jpg", "https://...2.jpg"]
  }
}
```

**HMAC Signature Calculation (Laravel):**
```php
$body = json_encode($payload);
$signature = hash_hmac('sha256', $body, config('ajinsafro.hmac_secret'));
// Send as header: X-AJ-Signature: $signature
```

## Files Structure

```
ajinsafro-core/
├── ajinsafro-core.php          # Main plugin file
├── README.md                    # This file
├── includes/
│   ├── Admin/
│   │   └── Settings.php         # Admin settings page
│   ├── Ajax/
│   │   └── Handler.php          # AJAX request handlers
│   ├── Core/
│   │   ├── Assets.php           # Assets enqueue
│   │   └── Options.php          # Options management
│   ├── Frontend/
│   │   └── Shortcode.php        # Shortcode handler
│   └── Sync/
│       ├── RestEndpoint.php     # REST API endpoint
│       └── TourSyncer.php       # Tour sync logic
├── assets/
│   ├── css/
│   │   ├── admin.css            # Admin styles
│   │   └── package-builder.css  # Frontend styles
│   └── js/
│       └── package-builder.js   # Frontend JavaScript
└── templates/
    ├── admin/
    │   └── settings.php         # Settings page template
    └── frontend/
        └── package-builder.php  # Package builder template
```

## API Reference

### WordPress Meta Keys (Tours)
- `_aj_laravel_voyage_id` - Link to Laravel voyage ID
- `address` - Tour address
- `duration_day` - Duration text
- `adult_price` - Adult price (in cents)
- `child_price` - Child price (in cents)
- `is_featured` - Featured flag (on/off)
- `is_sale_schedule` - Sale schedule flag (on/off)
- `discount_type` - Discount type
- `_thumbnail_id` - Featured image attachment ID
- `gallery` - Gallery image IDs (comma-separated)

### Custom Table (st_tours)
The plugin updates the TravelerWP custom table `{prefix}_st_tours` with tour data.

## Troubleshooting

### Plugin not showing in menu
- Check PHP version (must be 8.0+)
- Check for PHP errors in debug.log

### Shortcode not working
- Ensure you're on a `st_tours` single page
- Check that Laravel API URL is configured
- Check browser console for JavaScript errors

### Sync failing
- Verify HMAC secret matches between Laravel and WordPress
- Check sync is enabled in settings
- Check log file: `wp-content/uploads/ajinsafro-sync.log`

### Images not importing
- Check WordPress can write to uploads folder
- Verify image URLs are accessible
- Check PHP memory limit (recommend 256M+)

## Security

- HMAC-SHA256 signature verification for sync
- WordPress nonce verification for AJAX
- Rate limiting (30 requests/minute per IP)
- Input sanitization and validation
- No sensitive data in frontend

## Support

For issues or questions, contact Ajinsafro development team.

## License

Proprietary - Ajinsafro © 2026
