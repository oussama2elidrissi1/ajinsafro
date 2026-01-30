# Front Homepage – Design Breakdown (TravelerWP-like)

Based on the provided screenshot as the single source of truth.

---

## 1. Main sections

| Section | Description |
|--------|-------------|
| **Dark topbar** | Full-width bar at very top: dark grey/black background; left: phone icon + number, email icon + address (white text); right: social icons (Facebook, Twitter, Instagram, YouTube). Minimal padding, white text/icons. |
| **Semi-transparent header** | Overlays the hero image; light semi-transparent white background. Logo left (suitcase + AjiNsafro.ma + Arabic tagline); nav menu center (Home, Hotel, Tour, Activity, Rental, Car, Pages with dropdown carets); right: currency (EUR) dropdown, cart icon, user icon, blue “Become a host” button. |
| **Hero** | Full-width background image (e.g. tropical waterfalls), full viewport height; dark semi-transparent overlay. Centered H1 “Let the journey begin”, subtitle “Get the best prices on 2,000,000+ properties, worldwide”. |
| **Search tabs** | Horizontal tabs below hero text: Hotel (active – white bg), Tours, Activity, Rental, Cars Rental, Car Transfer. Rounded rectangular buttons; active white, others dark with white text; subtle shadow. |
| **Search form** | Single horizontal white bar, heavily rounded, centered, overlapping hero and content below; prominent shadow. Fields: Location (Where are you going?), Check in, Check out, Guests; vertical dividers; blue Search button on the right. |
| **Top destinations** | White background; centered heading “Top destinations”; grid of destination cards (image, title, optional meta). |

---

## 2. Layout rules

- **Centering**: Hero title/subtitle, search tabs, search form, and “Top destinations” heading are horizontally centered.
- **Spacing**: Comfortable vertical spacing between hero, form, and destinations; consistent padding in topbar and header.
- **Rounded corners**: Search form and tabs use large border-radius; “Become a host” and Search button rounded.
- **Shadows**: Search form has a clear box-shadow; tabs have a light shadow.
- **Overlay**: Hero uses a dark overlay (e.g. `bg-black/50`) so white text is readable.
- **Background**: Hero uses `background-size: cover` and `background-position: center` for the image.

---

## 3. Translation to Blade + Tailwind

- **Tailwind**: Front layout uses Tailwind via CDN so no `tailwind.config` or `package.json` changes; front stays isolated from admin.
- **Layout**: `resources/views/layouts/front.blade.php` – HTML shell, Tailwind CDN, `@yield('content')`; no admin CSS/JS.
- **Components**:
  - `components/front/navbar.blade.php`: topbar + header (logo, nav, actions).
  - `components/front/hero-search.blade.php`: hero section, overlay, H1/subtitle, tabs, GET form to `/search` with `location`, `check_in`, `check_out`, `guests`.
  - `components/front/destination-card.blade.php`: single card (image, title); used in a grid on home and optionally on search.
- **Views**: `front/home.blade.php` (navbar + hero-search + destinations grid), `front/search.blade.php` (navbar + same hero form with prefill + results area).
- **Images**: Hero from `public/front/images/hero.jpg`; destinations from `public/front/images/destinations/*.jpg` (or placeholders). Hero: `bg-cover bg-center` + overlay div.

---

## 4. Responsiveness

- **Desktop**: Full layout as above; nav inline; search form one row.
- **Tablet**: Topbar/header can stack or collapse; search form may wrap into 2 rows.
- **Mobile**: Hamburger menu for nav; search form stacked; destination grid 1 column; topbar text can be shortened or icon-only.

---

## 5. Images (where to place and use)

| Asset | Path | Usage |
|-------|------|--------|
| Hero background | `public/front/images/hero.jpg` | Hero section `background-image`, `background-size: cover`, `background-position: center`. |
| Destination images | `public/front/images/destinations/destination-1.jpg`, etc. | Destination cards; `object-cover` in a fixed-aspect container. |
| Logo | Optional: `public/front/images/logo.svg` or text-only | Navbar left; text “AjiNsafro.ma” + Arabic tagline if no image. |

If images are missing, use placeholder background color or a placeholder service so layout and overlay remain correct.
