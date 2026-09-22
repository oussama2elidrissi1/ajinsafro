# Catalogue historique ajinsafro.ma — import Laravel

`legacy-programs.json` contient les **89 programmes** récupérés de l'ancien site `ajinsafro.ma`
(80 fiches richement récupérées, 9 partielles, 103 URLs historiques). C'est le **même fichier que
celui du plugin WordPress de migration** : une seule source, pas de divergence entre les deux côtés.

## Lancer l'import

```bash
php artisan db:seed --class=LegacyProgramsSeeder
```

Le seeder n'est volontairement **pas** appelé par `DatabaseSeeder` : il s'agit de données catalogue
réelles, pas de jeu de démo, et on ne veut pas 89 brouillons sur chaque installation neuve.

## Ce que l'import crée

Pour chaque programme, un `Voyage` Laravel :

| Champ | Source |
|---|---|
| `name` | titre historique |
| `slug` | **dernier segment de l'ancienne URL `.ma`** (voir « URLs » plus bas) |
| `accroche` / `description` | résumé, durée, tarif relevé, note de récupération, lien source |
| `destination` | `Maroc` (voyage national), `Arabie Saoudite` (Omra), sinon vide |
| `duration_text` | durée historique |
| `price_from` / `old_price` | tarif et valeur historiques |
| `status` | `draft` |
| `tours_include` / `tours_exclude` | prestations incluses / non incluses |
| `travel_program_days` | une ligne par étape récupérée |
| thèmes | `omra`, `week-end`, `circuit` ou `sejour` quand le titre/la catégorie le dit clairement |

`status = draft` : la fiche reste **invisible du front public, du catalogue agent et du catalogue
partenaire**, et part en `draft` côté WordPress lors de la synchronisation.

## Ce que l'import ne crée jamais

Images, galeries, départs, disponibilités, chambres, vols. Une fiche importée **n'est pas vendable**
en l'état — c'est voulu.

## Les rendre visibles dans le catalogue admin

`Circuits > Voyages` est alimenté par WordPress : un voyage Laravel sans `wp_post_id` n'y apparaît
pas. Après le seed, pousser les programmes vers WordPress :

```bash
php artisan legacy:push-wp              # simulation
php artisan legacy:push-wp --execute    # écrit réellement
php artisan legacy:push-wp --execute --limit=5   # par lots
php artisan legacy:push-wp --execute --id=43 --id=46
```

La commande crée le tour `st_tours` en **brouillon**, avec `post_name` = slug historique (l'URL
publique est donc celle de l'ancien site, seul le domaine change), et pose `_aj_laravel_voyage_id`
et `_ajinsafro_legacy_id`. Si le plugin WordPress a déjà importé le programme, elle se rattache au
post existant au lieu d'en créer un second. Elle ne touche jamais un voyage déjà lié.

Les métas `adult_price`, `min_price`, `duration_day` et `address` sont écrites depuis Laravel
(quand elles sont renseignées) pour que la liste admin affiche prix et durée.

## La marque « À compléter »

Elle vit dans `voyages.logistics_meta` :

```json
{
  "legacy_import": { "source": "ajinsafro.ma", "legacy_id": 43, "recovery": "rich", "...": "..." },
  "seo":           { "legacy_path": "/voyage-national/circuit-...-43", "legacy_urls": ["..."] },
  "completion":    { "status": "incomplete", "label": "À compléter", "missing": ["images", "departs", "..."] }
}
```

Lecture côté code : `Voyage::isLegacyImport()`, `isLegacyIncomplete()`, `legacyMissing()`,
`legacyPath()`. Le badge **À compléter** s'affiche dans `Circuits > Voyages` (tableau et cartes),
avec le détail des manques en infobulle.

Clés possibles de `missing` : `images`, `programme_jours`, `departs`, `prix`, `duree`,
`destination`, `themes`, `contenu`, `url_publique`, `type_offre`, `lien_wordpress`.

Pour retirer la marque une fois la fiche reprise, passer
`logistics_meta.completion.status` à autre chose que `incomplete` (p. ex. `complete`) :
le seeder ne réécrira plus le contenu de cette fiche lors des passages suivants.

## URLs : même structure, seul le domaine change

Le slug Laravel reprend **exactement** le dernier segment de l'ancienne URL, et le chemin complet
est mémorisé dans `logistics_meta.seo.legacy_path` :

```
ancien :  https://www.ajinsafro.ma/voyage-national/circuit-ifrane-michlifen-azrou-meknes-04-mars-2018-43
nouveau : https://ajinsafro.net/voyage-national/circuit-ifrane-michlifen-azrou-meknes-04-mars-2018-43
```

84 programmes sur 89 conservent ainsi leur chemin à l'identique. Les 5 restants
(IDs `156, 379, 496, 515, 537`) n'avaient qu'une ancienne page de commande
(`/team/buy.php?id=...`) : leur chemin public reste à arbitrer et `url_publique` figure dans
leur liste `missing`.

Comme `WpTourSyncService` recopie `voyage.slug` dans `post_name`, `legacy:push-wp` et la
synchronisation Laravel → WP posent le bon slug WordPress. Reste à vérifier côté WordPress que la structure de permalien des
`st_tours` rejoue bien les préfixes `voyage-national` et `voyages-international`.

### Table de correspondance / redirections

```bash
php artisan legacy:url-map --domain=ajinsafro.net
```

Écrit `storage/app/legacy-url-map.csv` : `legacy_id, programme, ancien_chemin, action,
nouveau_chemin, nouvelle_url, wordpress, etat`. `action` vaut `identique` (rien à faire, seul le
domaine change) ou `301` pour les 20 variantes historiques (anciens tarifs dans le slug,
`/team/buy.php?id=...`, `/onedeal.php?id=...`).

## Idempotence

- Un programme est retrouvé par son `legacy_id` (dans `logistics_meta`), sinon par son slug.
- Relancer le seeder ne duplique rien.
- Une fiche dont `completion.status` n'est plus `incomplete` n'est **plus réécrite** : seules ses
  métadonnées de migration (URLs, lien WordPress) sont rafraîchies.
- Les jours de programme et les thèmes ne sont créés que si la fiche n'en a pas déjà.
- `wp_post_id` est renseigné automatiquement si le tour existe déjà côté WordPress
  (meta `_ajinsafro_legacy_id`). Le **seeder** n'écrit jamais dans les tables WordPress : seule
  `legacy:push-wp --execute` le fait, et uniquement pour les voyages non encore liés.

## Rappel sur le programme importé

`itinerary` est une suite d'**étapes** récupérées, pas un découpage jour par jour : les
`travel_program_days` créés sont une ossature à recaler sur la durée réelle (`programme_jours`
reste dans `missing` tant que ce n'est pas fait).
