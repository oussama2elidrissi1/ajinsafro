# Ajinsafro – Claude Project Guide

## Project overview

Ajinsafro is a travel platform built with **Laravel + WordPress (Traveler theme)**.

Core rule:

* **Laravel = back-office / business logic / CRUD / pricing / departures / reservations / program**
* **WordPress = SEO / public front / permalinks / templates / catalog pages**
* **WordPress must display Laravel-managed data as if it were native Traveler data**

This rule must never be broken by introducing parallel logic in the wrong side.

## Main architecture

### Laravel side

Used for:

* admin dashboards
* voyage CRUD
* reservations workspace
* business rules
* departures and capacities
* pricing and extras
* flights / hotels / activities / program management
* agency / role logic

Common URLs:

* `/admin/circuits/voyages`
* `/admin/reservations/workspace`
* `/admin/reservations/create`
* `/admin/settings/home-page`

### WordPress side

Used for:

* public travel pages
* SEO-friendly URLs
* Traveler integration
* homepage rendering
* catalog / search pages
* theme-compatible display

Main post types often involved:

* `st_tours`
* `st_hotel`

## Integration rule

The integration is primarily **direct database sync**, not API-first.

Important mapping:

* Laravel `voyages.wp_post_id` ↔ WordPress `wp_posts.ID`
* WordPress meta `_aj_laravel_voyage_id` ↔ Laravel voyage ID

When creating or updating tours, always preserve sync integrity between Laravel records and WordPress posts/meta.

## Known project components

### WordPress plugins

#### `ajinsafro-tour-bridge`

Responsibilities:

* override single `st_tours` rendering
* inject custom content into tour pages
* optionally auto-inject builder shortcode like `[aj_package_builder]`
* avoid duplicate shortcode injection

#### `ajinsafro-traveler-home`

Responsibilities:

* render custom homepage sections
* homepage settings driven from Laravel admin
* sections like:

  * holiday_theme
  * WhatsApp banner
  * explore section
  * cruises
* must be robust against JSON/array format differences in saved settings

## Important data areas

### Laravel tables often used

* `voyages`
* `departures`
* `travel_program_days`
* `voyage_images`
* `settings`

### WordPress data often used

* `wp_posts`
* `wp_postmeta`
* Traveler fields like:

  * `_thumbnail_id`
  * `gallery`
  * `tour_price_by`
  * `min_people`
  * `duration_day`
  * `multi_location`

### Custom WordPress tables sometimes used

* `aj_travel_dates`
* `aj_travel_departure_places`
* `aj_travel_departure_flights`

## Non-negotiable implementation rules

1. **Do not move business logic to WordPress if it belongs in Laravel.**
2. **Do not break Traveler compatibility on the public front.**
3. **Do not replace sync with ad-hoc duplicated storage.**
4. **Do not rely on CSS-only hacks when the real issue is in PHP/data flow.**
5. **Do not introduce debug-style UI in production-facing admin pages.**
6. **Prefer real fixes in controllers, queries, sync services, and templates.**
7. **When touching sync, always protect against duplicates.**
8. **When touching UI, keep it minimal, professional, and agent-friendly.**

## UI / UX expectations

Target users include:

* super admin
* siège admin
* branch admin
* chef commercial
* commercial
* agent

General design expectations:

* professional
* clean
* minimal
* responsive
* not developer-oriented
* no excessive helper text
* no noisy headers
* keep left navigation usable
* v2 agent/manager views should keep only the useful topbar/header treatment

## Reservations workspace direction

The reservations area should be:

* centered on the **voyage / offer**, not only the creator agent
* readable by managers across agents when needed
* connected to departures, capacity, rooms, and reservations lists
* robust in one-page workflow where possible

Important expectations:

* show relevant availability
* show departure-level stats
* show which agent created the reservation
* keep form flow simple
* dynamic extras by voyage

## Departures / rooms / flights rules

### Departures

Each departure can have:

* start date
* end date
* total capacity
* available capacity
* base / sale price
* notes
* possible legacy `wp_travel_date_id`

### Rooms

Room availability should be tied to departure + hotel logic.
Computed availability must use active rows and preserve correct reserved counts.

### Flights

Flights must be filtered by departure place.
Search bar and itinerary snippets must show consistent flight data.

## Common bug patterns to watch

### Homepage settings

Be careful with:

* JSON vs array storage
* string booleans like `"1"`, `"true"`, `"on"`
* duplicate section keys like `cruises`
* missing section rendering because of normalization issues

### Program days

Typical risks:

* only part of days displayed
* wrong DB connection
* JS tab re-init issues
* TinyMCE conflicts
* fragile eager loading

### Travel dates / departures sync

Typical risks:

* duplicate inserts for same date
* matching only by `wp_travel_date_id`
* failure to reuse existing rows by date
* unique constraint collisions in `aj_travel_dates`

### Catalog / workspace

Typical risks:

* Laravel-native voyages not appearing because WP lookup fails
* one failing WP row aborting whole list build
* fallback logic missing when `wp_post_id` is null

## Coding style for this project

When making changes:

* prefer small, targeted, production-safe edits
* preserve current architecture
* avoid rewriting unrelated modules
* keep file changes scoped
* respect existing naming and data flow
* add fallback handling where data may be partially missing
* do not add dead code or temporary hacks

## Expected workflow for implementation

When asked to change something:

1. identify the exact source file and execution path
2. confirm whether the issue is Laravel, WordPress, Traveler, JS, CSS, or sync
3. fix root cause, not just symptoms
4. preserve backward compatibility with existing data
5. test the real affected route/page
6. verify no duplicate / null / sync regressions are introduced

## Deployment / ops notes

Environment often includes:

* cPanel / Apache
* PHP 8.1
* MySQL / MariaDB
* git deployment from GitHub

Common server path example:

* `/home/ajinsafronet/public_html/booking`

Typical deployment commands include:

* `git fetch origin main`
* `git reset --hard origin/main`
* `php artisan optimize:clear`
* `php artisan view:cache`
* `php artisan config:cache`

When relevant, also consider:

* `php composer.phar install --no-dev --optimize-autoloader`
* `php artisan migrate --force`

## What to avoid

* breaking SEO URLs
* bypassing WordPress front rendering conventions
* storing the same truth in multiple places without sync strategy
* replacing PHP/template fixes with CSS patches only
* removing left navigation in admin layouts
* adding fake placeholder content in production lists
* keeping “Brouillon auto” style artifacts visible if real Laravel data exists

## Preferred output style for code assistance

When proposing changes for this project:

* be concrete
* mention exact files to edit
* explain root cause briefly
* give implementation-ready steps
* preserve production behavior
* favor copy-pasteable prompts for coding agents when requested

## Quick summary

If you touch Ajinsafro, always remember:

**Laravel owns the business. WordPress owns the public SEO front. Sync must stay reliable. UI must stay clean and professional. Fix root causes, not symptoms.**
