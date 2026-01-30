# Design interpretation – Front homepage & settings

## How the screenshot maps to navbar / topbar / hero / search bar

| Screenshot element | Implementation |
|-------------------|----------------|
| **Dark topbar** | `components/front/navbar.blade.php`: dark bar with phone, email, social icons. Rendered only if at least one of `topbar_phone`, `topbar_email`, or any social URL is set (from settings). |
| **Semi-transparent header** | Same component: header with `bg-white/90 backdrop-blur-sm`. Logo (from `brand_logo` or fallback icon) + `brand_name` left; nav center; currency/cart/user + “Become a host” right. |
| **Hero black area** | `components/front/hero-search.blade.php`: full-height section. Background is either **image** or **video** (from settings `hero_type`, `hero_image`, `hero_video`). |
| **Hero overlay** | A div `absolute inset-0` with `background: rgba(0,0,0, OPACITY)`. `OPACITY` = `hero_overlay_opacity` (0–1) from settings. |
| **Hero title / subtitle** | Centered H1 and paragraph; text = `hero_title` and `hero_subtitle` from settings. |
| **Search tabs + bar** | Unchanged: horizontal tabs (Hotel, Tours, etc.) and rounded white form (location, check-in, check-out, guests, Search). Layout matches screenshot. |

## Image vs video background

- **hero_type = image**  
  - Background: a div with `background-image: url(...)` and `bg-cover bg-center`.  
  - Source: if `hero_image` is set → `Storage::disk('public')->url(hero_image)`; else fallback `asset('front/images/hero.jpg')`.

- **hero_type = video**  
  - Background: `<video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline>`.  
  - Source: `Storage::disk('public')->url(hero_video)`.  
  - Only shown when `hero_video` is set; otherwise image fallback is used.

Overlay is applied the same way in both cases: a full-bleed div with `rgba(0,0,0, hero_overlay_opacity)` so text stays readable.

## Settings access on front

- **No global helper:** Front Blade components use the Setting model directly: `\App\Models\Setting::getValue('key', $default)` and `\App\Models\Setting::storageUrl($path)`.
- **Defaults:** In `Setting::$defaults` (brand_name, topbar_phone, topbar_email, hero_type, hero_overlay_opacity, hero_title, hero_subtitle, etc.). Used when no row exists in `settings` table.
- No extra config file; no caching added (project did not use settings cache).

## Where assets are stored and how to replace them from admin

| Asset | Stored path (public disk) | Public URL | How to replace |
|-------|---------------------------|-----------|----------------|
| Logo | `storage/app/public/front/brand/` | `/storage/front/brand/<filename>` | Admin → Paramètres généraux → Logo (image). Upload replaces previous. |
| Hero image | `storage/app/public/front/hero/` | `/storage/front/hero/<filename>` | Admin → Paramètres généraux → Type = Image → Image hero. |
| Hero video | `storage/app/public/front/hero/` | `/storage/front/hero/<filename>` | Admin → Paramètres généraux → Type = Vidéo → Vidéo hero. |

Ensure `php artisan storage:link` has been run so `public/storage` → `storage/app/public` and URLs work.
