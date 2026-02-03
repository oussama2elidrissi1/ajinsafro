# 🧪 WordPress Import - Testing Guide

## ⚠️ Local vs Production Database

### Current Situation

**Local environment (.env):**
```
DB_DATABASE=common_admin
```

**Production environment (expected):**
```
DB_DATABASE=ajinsafronet_wp_tkrpc
```

### Test Results

```bash
$ php artisan tinker --execute="echo DB::table('cFdgeZ_posts')->where('post_type', 'st_tours')->count() . ' tours found';"
0 tours found
```

**Conclusion:** The WordPress data is **only on the production database**, not in your local `common_admin` database.

---

## 🚀 Deployment to Production

### Step 1: Upload the new files

Upload these files to your production server:

```
app/Services/Wp/WpTourImporter.php
app/Console/Commands/WpImportTours.php
WP_IMPORT_GUIDE.md
```

### Step 2: SSH into production server

```bash
ssh your_user@your_server.com
cd /path/to/laravel/app
```

### Step 3: Verify WordPress database connection

```bash
php artisan tinker
```

```php
// Check if WordPress tables exist
DB::table('cFdgeZ_posts')->count();

// Check st_tours count
DB::table('cFdgeZ_posts')
    ->where('post_type', 'st_tours')
    ->where('post_status', 'publish')
    ->count();
// Expected: 26 (or your actual number of published tours)
```

### Step 4: Run the import

```bash
# Import all tours
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
+----+------------+--------------------------------+----------------------------+
| ID | WP Post ID | Name                           | Slug                       |
+----+------------+--------------------------------+----------------------------+
| 1  | 123        | Séjour Dubaï 7 jours          | sejour-dubai-7-jours      |
| 2  | 124        | Circuit Marrakech 5 jours     | circuit-marrakech-5-jours |
| 3  | 125        | Découverte Istanbul 6 jours   | decouverte-istanbul-6-jours|
| 4  | 126        | Tour Egypte 10 jours          | tour-egypte-10-jours      |
| 5  | 127        | Voyage Tunisie 8 jours        | voyage-tunisie-8-jours    |
+----+------------+--------------------------------+----------------------------+

📈 Statistics:
  • Synced from WordPress: 26
  • Active tours: 26
  • Draft tours: 0

⏱️  Completed in 3.45 seconds
```

### Step 5: Verify the import

```bash
php artisan tinker
```

```php
// Check total voyages
\App\Models\Voyage::count();
// Expected: 26

// Show first 5
\App\Models\Voyage::orderBy('id')->limit(5)->get(['id', 'wp_post_id', 'name', 'slug']);

// Check synced tours
\App\Models\Voyage::whereNotNull('wp_post_id')->count();
// Expected: 26

// View a detailed tour
$voyage = \App\Models\Voyage::first();
$voyage->toArray();
```

---

## 🧪 Local Testing (Optional)

If you want to test locally without production data, you can:

### Option 1: Dump production WordPress tables and import locally

**On production server:**
```bash
mysqldump -u username -p ajinsafronet_wp_tkrpc \
  cFdgeZ_posts \
  cFdgeZ_postmeta \
  cFdgeZ_st_tours \
  --where="post_type='st_tours'" \
  > wp_tours_dump.sql
```

**Download and import locally:**
```bash
# Download the dump
scp user@server:/path/to/wp_tours_dump.sql .

# Import into local database
mysql -u root common_admin < wp_tours_dump.sql
```

### Option 2: Create test WordPress data locally

Create a seeder to generate fake WordPress tours for testing:

```bash
php artisan make:seeder WpToursTestSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WpToursTestSeeder extends Seeder
{
    public function run()
    {
        // Create test st_tours posts
        $tours = [
            [
                'ID' => 1001,
                'post_title' => 'Test Tour Dubai',
                'post_name' => 'test-tour-dubai',
                'post_content' => 'Description of the test tour',
                'post_excerpt' => 'Short description',
                'post_status' => 'publish',
                'post_type' => 'st_tours',
            ],
            // Add more test tours...
        ];

        foreach ($tours as $tour) {
            DB::table('cFdgeZ_posts')->insert($tour);
            
            // Add st_tours data
            DB::table('cFdgeZ_st_tours')->insert([
                'post_id' => $tour['ID'],
                'adult_price' => 10900, // 109.00 MAD
                'duration_day' => '7 days / 6 nights',
                'address' => 'Dubai, UAE',
            ]);
        }
    }
}
```

Then run:
```bash
php artisan db:seed --class=WpToursTestSeeder
php artisan wp:import-tours --all
```

---

## 📊 Production Checklist

Before running on production:

- [ ] SSH access to production server
- [ ] Files uploaded to production
- [ ] Database connection verified (`DB::table('cFdgeZ_posts')->count()`)
- [ ] WordPress tables accessible (`cFdgeZ_posts`, `cFdgeZ_postmeta`, `cFdgeZ_st_tours`)
- [ ] At least 1 published `st_tours` post exists
- [ ] Backup of `voyages` table (if it has existing data)

Running the import:

- [ ] `php artisan wp:import-tours --all` executed
- [ ] No errors in output
- [ ] Expected number of tours created/updated
- [ ] Verification shows correct count
- [ ] Sample voyages display correctly

Post-import verification:

- [ ] `Voyage::count()` matches expected number
- [ ] All voyages have `wp_post_id` set
- [ ] Slugs are unique
- [ ] Prices are correct (in cents)
- [ ] `wp_synced_at` is set
- [ ] Test API endpoint: `/api/public/tours/{voyageId}/package-state`

---

## 🔄 Regular Sync Workflow

### When to re-run the import

Run `php artisan wp:import-tours --all` when:
- New tours are added in WordPress
- Tour data is updated in WordPress (name, price, description, etc.)
- Tours are published/unpublished in WordPress

### Automated sync (optional)

You can schedule the import to run automatically:

**In `app/Console/Kernel.php`:**
```php
protected function schedule(Schedule $schedule)
{
    // Import WordPress tours every hour
    $schedule->command('wp:import-tours --all')
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground();
}
```

Or create a webhook in WordPress to trigger the import on post update.

---

## 🎯 Next Steps After Import

Once the import is successful:

1. **Test the API:**
   ```bash
   curl https://booking.ajinsafro.net/api/public/tours/1/package-state
   ```

2. **Sync voyages back to WordPress** (if needed):
   - Use the existing Laravel → WP sync service
   - Update `wp_post_id` mapping

3. **Add travel_day_items:**
   - Create items for each voyage via admin or API
   - Populate with flights, hotels, activities, etc.

4. **Configure WordPress plugin:**
   - Install `ajinsafro-core` plugin
   - Set Laravel API URL
   - Test `[aj_package_builder]` shortcode

5. **Test the complete flow:**
   - WordPress tour page → Package Builder → Laravel API → Checkout

---

## 📞 Support

### If the import fails

1. **Check the error message:**
   - Look for database connection errors
   - Check for missing tables
   - Verify table prefix

2. **Check the logs:**
   ```bash
   tail -50 storage/logs/laravel.log
   ```

3. **Verify database access:**
   ```php
   DB::table('cFdgeZ_posts')->count();
   DB::table('cFdgeZ_st_tours')->count();
   DB::table('voyages')->count();
   ```

4. **Test with a single tour:**
   ```bash
   # Find a tour ID
   php artisan tinker
   >>> DB::table('cFdgeZ_posts')->where('post_type', 'st_tours')->first()->ID
   
   # Import it
   php artisan wp:import-tours --wp_id=123
   ```

5. **Check for slug conflicts:**
   ```php
   \App\Models\Voyage::pluck('slug', 'id');
   ```

---

## ✅ Success Indicators

After a successful import:

✅ Command completes without errors  
✅ Summary shows created/updated tours  
✅ `Voyage::count()` matches WordPress tour count  
✅ All voyages have `wp_post_id` != NULL  
✅ Slugs are unique  
✅ Prices are in cents (e.g., 10900)  
✅ API endpoint returns 200 + JSON  
✅ Sample tours display correctly in admin  

**Ready for production! 🚀**
