# 📥 WordPress Tour Import Guide

## 🎯 Overview

This system imports **all WordPress TravelerWP tours** (post_type=`st_tours`) from the WordPress database into Laravel's `voyages` table.

### Key Features
- ✅ **Idempotent**: Safe to run multiple times
- ✅ **Efficient**: Uses optimized queries (no N+1 problem)
- ✅ **Smart mapping**: Merges data from `wp_posts`, `cFdgeZ_st_tours`, and `cFdgeZ_postmeta`
- ✅ **Slug handling**: Ensures unique slugs with automatic suffixing
- ✅ **Sync tracking**: Uses `wp_sync_hash` to detect changes
- ✅ **Transaction safe**: Uses database transactions per batch

---

## 📋 Prerequisites

### Database Connection
The import uses your **current Laravel database connection** (same as WordPress):
- Database: `ajinsafronet_wp_tkrpc` (or as configured in `.env`)
- Tables accessed:
  - `cFdgeZ_posts` (WordPress posts)
  - `cFdgeZ_postmeta` (WordPress post metadata)
  - `cFdgeZ_st_tours` (TravelerWP tour data)
  - `voyages` (Laravel destination table)

### Required Models
- `App\Models\Voyage` (already exists)

### Required Services
- `App\Services\Wp\WpTourImporter` (created)

### Required Commands
- `App\Console\Commands\WpImportTours` (created)

---

## 🚀 Usage

### Command Syntax

```bash
php artisan wp:import-tours [options]
```

### Options

| Option | Description | Required |
|--------|-------------|----------|
| `--all` | Import all published st_tours | Yes* |
| `--wp_id={ID}` | Import a specific WordPress post by ID | Yes* |
| `--limit={N}` | Limit number of tours to import (default: 0 = no limit) | No |

*Either `--all` or `--wp_id` must be specified.

---

## 📝 Examples

### 1. Import ALL tours

```bash
php artisan wp:import-tours --all
```

**Expected output:**
```
═══════════════════════════════════════════════════════
   WordPress Tour Importer - TravelerWP → Laravel
═══════════════════════════════════════════════════════

🔄 Importing all published st_tours from WordPress (no limit)...

 26 tours processed | 3s elapsed

═══════════════════════════════════════════════════════
   Import Summary
═══════════════════════════════════════════════════════

+-------------------------+-------+
| Metric                  | Count |
+-------------------------+-------+
| ✅ Created              | 26    |
| 🔄 Updated              | 0     |
| ⏭️  Skipped (no changes)| 0     |
| ❌ Errors               | 0     |
+-------------------------+-------+

═══════════════════════════════════════════════════════
   Verification
═══════════════════════════════════════════════════════

📊 Total voyages in database: 26

📋 Sample voyages (first 5):
...

⏱️  Completed in 3.45 seconds
```

---

### 2. Import with limit (first 10 tours)

```bash
php artisan wp:import-tours --all --limit=10
```

**Use case:** Testing the import on a subset before running the full import.

---

### 3. Import a single tour

```bash
php artisan wp:import-tours --wp_id=123
```

**Expected output:**
```
🔄 Importing WordPress tour: 123...

✅ Created successfully.

📋 Voyage Details:
+------------+-----------------------------------+
| Field      | Value                             |
+------------+-----------------------------------+
| ID         | 1                                 |
| WP Post ID | 123                               |
| Name       | Séjour Dubaï 7 jours             |
| Slug       | sejour-dubai-7-jours             |
| Destination| Dubaï, Émirats Arabes Unis       |
| Duration   | 7 jours / 6 nuits                |
| Price From | 109.00 MAD                        |
| Status     | actif                             |
| Synced At  | 2026-02-03 10:30:45              |
+------------+-----------------------------------+
```

---

### 4. Re-import (update existing tours)

```bash
php artisan wp:import-tours --all
```

**Behavior:**
- Tours with **changed data**: Updated (🔄)
- Tours with **no changes**: Skipped (⏭️)
- **New tours**: Created (✅)

The system uses `wp_sync_hash` to detect changes efficiently.

---

## 🗺️ Data Mapping

### WordPress → Laravel Field Mapping

| Laravel Field | WordPress Source | Priority |
|--------------|------------------|----------|
| `wp_post_id` | `wp_posts.ID` | - |
| `name` | `wp_posts.post_title` | - |
| `slug` | `wp_posts.post_name` | Unique check + auto-suffix |
| `description` | `wp_posts.post_content` | - |
| `accroche` | `wp_posts.post_excerpt` | - |
| `destination` | 1. `st_tours.address`<br>2. `postmeta(address)` | First non-null |
| `duration_text` | 1. `st_tours.duration_day`<br>2. `postmeta(duration_day)` | First non-null |
| `price_from` | 1. `st_tours.adult_price`<br>2. `st_tours.min_price`<br>3. `postmeta(adult_price)`<br>4. `postmeta(min_price)` | First > 0 |
| `old_price` | `NULL` | Not imported |
| `currency` | `'MAD'` | Default |
| `min_people` | `NULL` | Not imported |
| `departure_policy` | `NULL` | Not imported |
| `status` | `'actif'` if `publish`, else `'brouillon'` | - |
| `featured_image` | `NULL` | Not imported (can be enhanced) |
| `wp_synced_at` | `now()` | Timestamp |
| `wp_sync_hash` | `sha256(json_encode(data))` | Change detection |

---

## 🔍 Verification

### Quick verification in Tinker

```bash
php artisan tinker
```

**Check total count:**
```php
\App\Models\Voyage::count();
// Expected: 26
```

**Show first 5 voyages:**
```php
\App\Models\Voyage::orderBy('id')->limit(5)->get(['id', 'wp_post_id', 'name', 'slug']);
```

**Find a specific tour by WP ID:**
```php
\App\Models\Voyage::where('wp_post_id', 123)->first();
```

**Check synced tours:**
```php
\App\Models\Voyage::whereNotNull('wp_post_id')->count();
```

**Check active vs draft:**
```php
\App\Models\Voyage::where('status', 'actif')->count(); // Active
\App\Models\Voyage::where('status', 'brouillon')->count(); // Draft
```

**View detailed info:**
```php
$voyage = \App\Models\Voyage::first();
$voyage->toArray();
```

---

## 🛠️ How It Works

### 1. Service: `WpTourImporter`

**Location:** `app/Services/Wp/WpTourImporter.php`

**Methods:**
- `importAll(int $limit = 0): array` - Import all tours with optional limit
- `importOne(int $wpPostId): array` - Import a single tour
- `importPost($post, $stToursData, $postMeta): string` - Process individual post
- `mapWpPostToVoyage($post, $stTour, $meta): array` - Map WP data to Laravel
- `ensureUniqueSlug(string $slug, int $wpPostId): string` - Handle slug conflicts

**Performance optimization:**
- ✅ Fetches all posts in **one query**
- ✅ Fetches `st_tours` data in **one query** with JOIN
- ✅ Fetches postmeta in **one query** and groups by post_id
- ✅ No N+1 queries

**Idempotency:**
- Uses `wp_sync_hash` (SHA256 of mapped data)
- Compares hash before updating
- Skips if no changes detected

**Slug uniqueness:**
- Checks if slug exists for another voyage
- Appends `-{wp_post_id}` if conflict
- Example: `sejour-dubai` → `sejour-dubai-123`

---

### 2. Command: `WpImportTours`

**Location:** `app/Console/Commands/WpImportTours.php`

**Features:**
- ✅ Interactive progress bar
- ✅ Colored output (emoji + formatting)
- ✅ Detailed summary with statistics
- ✅ Error reporting
- ✅ Verification output
- ✅ Sample data display

**Modes:**
- **Batch mode** (`--all`): Import all tours with progress tracking
- **Single mode** (`--wp_id`): Import one tour with detailed output

---

## ⚠️ Important Notes

### Slug Conflicts
If a slug already exists in `voyages` for another record:
- The importer appends `-{wp_post_id}` automatically
- Example: `tour-paris` becomes `tour-paris-456`
- This ensures uniqueness without losing the original slug structure

### Price Storage
Prices are stored in **cents** (multiply by 100):
- WordPress: `1090` (109.00 MAD)
- Laravel: `10900` (109.00 MAD in cents)
- Display: `109.00 MAD` (divide by 100 when displaying)

### Sync Hash
The `wp_sync_hash` field stores a SHA256 hash of the mapped data:
- Used to detect changes efficiently
- Avoids unnecessary database writes
- Computed from: name, slug, description, accroche, destination, duration, price, status

### Database Connection
The import uses the **current Laravel database connection**:
- No separate WordPress connection needed
- Uses `DB` facade with default connection
- WordPress tables are in the same database

---

## 🐛 Troubleshooting

### Issue 1: No tours found

**Symptoms:**
```
No published st_tours found to import.
```

**Solutions:**
1. Check WordPress database connection:
   ```php
   DB::table('cFdgeZ_posts')->where('post_type', 'st_tours')->count();
   ```
2. Verify table prefix is `cFdgeZ_`
3. Check if tours are published:
   ```php
   DB::table('cFdgeZ_posts')
       ->where('post_type', 'st_tours')
       ->pluck('post_status');
   ```

---

### Issue 2: Table not found

**Symptoms:**
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'cFdgeZ_posts' doesn't exist
```

**Solutions:**
1. Verify database name in `.env`:
   ```
   DB_DATABASE=ajinsafronet_wp_tkrpc
   ```
2. Check if WordPress is in the same database:
   ```bash
   php artisan tinker
   DB::select('SHOW TABLES');
   ```
3. Verify table prefix:
   ```php
   DB::table('cFdgeZ_posts')->count();
   ```

---

### Issue 3: Duplicate slug error

**Symptoms:**
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry for key 'voyages_slug_unique'
```

**Solutions:**
The importer already handles this automatically. If you still see this error:
1. The slug might be reserved by a non-WP voyage
2. Check existing slugs:
   ```php
   \App\Models\Voyage::pluck('slug', 'id');
   ```
3. The importer will append `-{wp_post_id}` to resolve conflicts

---

### Issue 4: Performance is slow

**Symptoms:**
Import takes > 10 seconds for 26 tours

**Solutions:**
1. Check database indexes:
   ```sql
   SHOW INDEXES FROM voyages WHERE Key_name = 'voyages_wp_post_id_index';
   ```
2. The importer is already optimized with batch queries
3. Add `--limit=10` to test on a subset first

---

## 📊 Expected Results

### For 26 WordPress Tours

**First run (all new):**
```
✅ Created: 26
🔄 Updated: 0
⏭️  Skipped: 0
❌ Errors: 0
```

**Second run (no changes):**
```
✅ Created: 0
🔄 Updated: 0
⏭️  Skipped: 26
❌ Errors: 0
```

**After updating 5 tours in WP:**
```
✅ Created: 0
🔄 Updated: 5
⏭️  Skipped: 21
❌ Errors: 0
```

---

## 🔄 Workflow

### Initial Import
```bash
# 1. Import all tours
php artisan wp:import-tours --all

# 2. Verify count
php artisan tinker
>>> \App\Models\Voyage::count()
=> 26

# 3. Check first tour
>>> \App\Models\Voyage::first()
```

### Regular Sync (after WP changes)
```bash
# Re-run the import (idempotent)
php artisan wp:import-tours --all
```

### Test Single Tour
```bash
# Import one specific tour
php artisan wp:import-tours --wp_id=123
```

---

## 📚 Related Documentation

- **API Documentation:** `PACKAGE_BUILDER_README.md`
- **Deployment Guide:** `DEPLOYMENT_FIX_404.md`
- **API Status Report:** `API_STATUS_REPORT.md`

---

## ✅ Checklist

After running the import, verify:

- [ ] `php artisan wp:import-tours --all` completed successfully
- [ ] Total voyages count = 26 (or expected number)
- [ ] Sample voyages displayed correctly
- [ ] All voyages have `wp_post_id` set
- [ ] Slugs are unique
- [ ] Prices are in cents (e.g., 10900 = 109.00 MAD)
- [ ] `wp_synced_at` is set for all imported voyages
- [ ] Active tours have `status='actif'`
- [ ] No errors in summary

---

## 🎉 Success Criteria

✅ **26 voyages imported**  
✅ **All have wp_post_id**  
✅ **Unique slugs**  
✅ **Correct mapping**  
✅ **Idempotent (safe to re-run)**

**Ready for production!** 🚀
