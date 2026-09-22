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
pas.

### 1. Diagnostic obligatoire

```bash
php artisan legacy:check          # lecture seule
php artisan legacy:check --full   # liste aussi les programmes sans équivalent WP
```

La commande vérifie quel accès WordPress fonctionne sur l'environnement, puis cherche pour chaque
programme un tour déjà présent côté WordPress :

| Correspondance | Signification |
|---|---|
| `meta` | le tour porte déjà `_ajinsafro_legacy_id` |
| `slug exact` | même `post_name` que le slug historique |
| `slug proche` | **doublon probable** : même slug au suffixe numérique près |
| `aucun` | à créer |

Le cas `slug proche` est réel : plusieurs programmes historiques existent déjà dans WordPress sous
un slug dédoublonné, par exemple `…-a-partir-de-8900-dhs-2` au lieu de `…-a-partir-de-8900-dhs-466`
(Barcelone, programme 466). Les publier sans précaution créerait deux tours pour la même offre.

### 2. Publication

```bash
php artisan legacy:push-wp                       # simulation
php artisan legacy:push-wp --execute --limit=5    # premier lot
php artisan legacy:push-wp --execute              # le reste
php artisan legacy:push-wp --execute --adopt      # rattache aussi les doublons probables
php artisan legacy:push-wp --execute --id=43 --id=46
```

Sans `--adopt`, un programme dont le slug est proche d'un tour existant est **signalé et laissé de
côté** — aucun doublon n'est créé. Avec `--adopt`, le voyage Laravel est rattaché au tour WordPress
existant (`wp_post_id`, `_aj_laravel_voyage_id`, `_ajinsafro_legacy_id`) sans en créer un second, et
le slug WordPress en place est conservé. Un tour déjà lié à un autre voyage Laravel n'est jamais
repris.

Les tours créés le sont en **brouillon**, avec `post_name` = slug historique (l'URL publique est
celle de l'ancien site, seul le domaine change) et les métas `adult_price`, `min_price`,
`duration_day`, `address`, `tours_include`, `tours_exclude` renseignées depuis Laravel.

### Note sur l'accès WordPress

`legacy:check` teste aussi `App\Repositories\WpRepository`. Celui-ci préfixe les tables lui-même
alors que la connexion `wp` applique déjà son propre préfixe : si la sonde renvoie `KO ... no such
table: cFdgeZ_cFdgeZ_posts`, tout ce qui passe par `WpRepository` / `WpTourSyncService` est inopérant
sur l'environnement. C'est pourquoi `legacy:push-wp` écrit via `App\Models\Wp\WpPost`, le même
accès que le catalogue admin.

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

`legacy:push-wp` recopie `voyage.slug` dans `post_name` : les tours qu'il crée portent donc le slug
historique. Attention, les tours **adoptés** (`--adopt`) gardent leur slug WordPress actuel — si vous
voulez restaurer l'URL d'origine sur ceux-là, il faut renommer le `post_name` et poser une 301 depuis
l'ancien, car ces pages sont déjà en ligne.

Reste à vérifier côté WordPress que la structure de permalien des `st_tours` rejoue bien les
préfixes `voyage-national` et `voyages-international`.

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
