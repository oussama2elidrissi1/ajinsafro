# Programme par jours + Activités (Ajinsafro)

## Résumé

- **Laravel** : CRUD des activités (catalogue) et gestion du programme jour par jour dans l’édition d’un voyage (onglet « Programme (Jours) »). Données en base WordPress : `aj_tour_days`, `aj_tour_day_activities`, `aj_activities`.
- **WordPress (plugin Ajinsafro Tour Bridge)** : Lecture seule de ces tables pour afficher l’itinéraire (mode Libre / Programme + liste d’activités par jour). Fallback sur `tours_program` WP si aucun jour Laravel.

## Tables (DB WordPress, préfixe `cFdgeZ_`)

- **aj_activities** : catalogue (id, title, slug, description, icon, default_duration_minutes, location_text).
- **aj_tour_days** : jours du tour (tour_id = wp_posts.ID, day_number, title, description, mode, day_title, notes, …).
- **aj_tour_day_activities** : lien jour ↔ activité (tour_id, day_id, activity_id, sort_order, is_included, is_mandatory, is_editable, custom_title, custom_description, start_time, end_time).

## Migrations Laravel

Exécuter sur la connexion `wp` :

```bash
php artisan migrate --database=wp
```

Cela crée `aj_activities`, `aj_tour_day_activities` et ajoute `mode`, `day_title`, `notes` à `aj_tour_days` si la table existe déjà (créée par le plugin).

## Exemple d’affichage front (WordPress)

- **Jour 1 – Mode Programme**  
  Titre : « Jour 1 - Arrivée ». Description/notes du jour. Liste d’activités : « Transfert aéroport » (Obligatoire), « Check-in hôtel », « Dîner libre ».
- **Jour 2 – Mode Libre**  
  Badge « Jour libre ». Liste d’activités optionnelles : « Option : visite souk », « Option : spa ».

Règles d’affichage :

- `mode = free` → badge « Jour libre » + liste des activités incluses.
- `mode = program` → description/notes du jour + liste des activités incluses.
- Pour chaque activité : `custom_title` si présent, sinon `activity.title` ; idem pour la description. Badge « Obligatoire » si `is_mandatory = 1`.

## Test rapide

1. **Laravel** : Créer 2–3 activités dans Admin → Circuits → Activités. Éditer un voyage (st_tours), onglet « Programme (Jours) » : définir le mode (Libre/Programme), titre du jour, notes, ajouter des activités (obligatoire/inclus, overrides).
2. **WordPress** : Ouvrir la page single du même tour ; la section Itinéraire doit afficher les jours avec mode, titre, et la liste des activités (avec badge « Obligatoire » si besoin).

Fallback : si `aj_tour_days` est vide pour ce tour, le plugin utilise comme avant la meta WordPress `tours_program`.
