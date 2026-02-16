# Tests Manuels - Ensure Location + Front Destinations

## 1) Ensure Location (MA, Casablanca) - 1er appel

Exemple avec Bearer token (secret partagé existant `ajsync_webhook_secret`):

```bash
curl -X POST "https://YOUR-WP-DOMAIN/wp-json/ajinsafro/v1/ensure-location" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SHARED_SECRET" \
  -d '{"country_code":"MA","city_name":"Casablanca"}'
```

Résultat attendu:
- HTTP `200`
- JSON avec `id` numérique
- `name` = `Casablanca`
- `country_code` = `MA`
- `created` = `true` (au premier appel sur une base sans cette ville)

## 2) Ensure Location (MA, Casablanca) - 2e appel (idempotence)

Relancer la même requête.

Résultat attendu:
- HTTP `200`
- Même `id` que le premier appel
- `created` = `false`

## 3) Vérification Front Tour

Sur une page `single st_tours` ayant cette location dans ses metas (`st_location_id` / `location_id` / `id_location` / `multi_location`):
- Une section `Destinations` doit être visible.
- Affichage attendu: `Maroc > Casablanca` (ou variante existante du pays en base, ex: `Morocco > Casablanca`).
- Si plusieurs destinations: 3 chips max visibles + chip `+X`.
