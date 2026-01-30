# Why `setting()` failed and how it was fixed

## Why `setting()` failed

The front Blade views were calling `setting('key')` (and `setting('key', $default)`). A global helper `setting()` was defined in `AppServiceProvider::boot()` with `if (! function_exists('setting')) { function setting(...) { ... } }`.

**Why you saw "Call to undefined function setting()":**

1. **Scope / load order** – In some environments or when views are compiled/cached, the helper can be undefined in the scope where Blade runs (e.g. view renderer may not have the same global scope where `AppServiceProvider::boot()` registered the function).
2. **No global helper desired** – Relying on a global helper for settings is brittle and was explicitly removed in favor of using the Setting model directly.

## How it was fixed

1. **Removed the global helper**  
   The `setting()` function was removed from `AppServiceProvider::boot()`. No other code in `AppServiceProvider` was changed.

2. **Replaced all usages with the Setting model**  
   Every call to `setting(...)` in front Blade was replaced with the existing static methods on `Setting`:

   - **Read a value:** `\App\Models\Setting::getValue('key', $default)`  
     Same behavior as before: reads from the `settings` table; if no row exists, returns the given default or the value from `Setting::$defaults`.

   - **File URL for uploads:** `\App\Models\Setting::storageUrl($path)`  
     Used for logo, hero image, and hero video. Already existed on the model; front components now call it directly.

3. **Where changes were made**
   - `resources/views/components/front/navbar.blade.php` – all `setting(...)` replaced with `\App\Models\Setting::getValue(...)`; logo URL uses `Setting::storageUrl($brandLogo)`.
   - `resources/views/components/front/hero-search.blade.php` – all `setting(...)` replaced with `\App\Models\Setting::getValue(...)`; hero image/video URLs use `Setting::storageUrl(...)`.

4. **Hero video upload**
   - Controller already used `$request->file('hero_video')->store('front/hero', 'public')` and the public disk.
   - Validation for `hero_video` was updated to `max:51200` (50 MB in KB) so large videos are accepted without changing config.
   - If `hero_type` is video but no video file is stored, the hero component already falls back to the hero image (or static fallback).

**Result:** The front no longer depends on any global helper. All settings are read and all storage URLs are built via the Setting model, which uses the same DB table and storage disk as the admin parametres-generaux page.
