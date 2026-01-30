# Front images

## Configurable from admin (storage)

Logo and hero image/video are managed in **Admin → Paramètres généraux**. They are stored under the **public** disk:

- **Logo:** `storage/app/public/front/brand/` → URL `/storage/front/brand/...`
- **Hero image / video:** `storage/app/public/front/hero/` → URL `/storage/front/hero/...`

Run `php artisan storage:link` if not already done.

## Static fallbacks (this folder)

| File | Usage |
|------|--------|
| `hero.jpg` | Hero fallback when no hero image is set in settings. Used with `background-size: cover` and dark overlay. |
| `destinations/dubai.jpg` | Top destinations card (Dubai). |
| `destinations/paris.jpg` | Top destinations card (Paris). |
| `destinations/tokyo.jpg` | Top destinations card (Tokyo). |
| `destinations/newyork.jpg` | Top destinations card (New York). |

If a file is missing, the hero falls back to the gray background; destination cards use an inline placeholder.
