# Catalogue historique ajinsafro.ma — import Laravel

`legacy-content-194.json` contient les **194 programmes** récupérés de l'ancien site `ajinsafro.ma`
(extraction du 23/09/2026 : 176 fiches complètes, 18 partielles, 225 anciennes URLs). Il remplace
`legacy-programs.json` (89 programmes, contenu partiel) dont il est un **sur-ensemble strict**.

`legacy-redirections-301.csv` est la table de redirections fournie avec l'extraction (225 lignes),
conservée comme référence.

## Lancer l'import

```bash
php artisan db:seed --class=LegacyContentSeeder --force
```

Keyé sur `legacy_id`, donc les 89 déjà importés sont **mis à jour et enrichis**, et les 105 autres
créés. `LegacyProgramsSeeder` (jeu de 89) est conservé pour l'historique mais n'a plus à être lancé.

Le seeder n'est volontairement **pas** appelé par `DatabaseSeeder` : ce sont des données catalogue
réelles, pas un jeu de démo.

## Ce que l'import remplit

| Donnée source | Destination |
|---|---|
| `titre_h1` / `h1` | `voyages.name` |
| URL SEO d'origine | `voyages.slug` (voir « URLs ») |
| `meta_description` | `voyages.accroche` |
| `description_longue` + hôtels + suppléments + notes | `voyages.description` |
| `prix_actuel` / `prix_barre` / `devise` | `price_from` / `old_price` / `currency` |
| `duree_jours` + `duree_nuits` | `duration_text` |
| `destinations[]` + segment | `destination` |
| `inclus[]` / `non_inclus[]` | `tours_include` / `tours_exclude` |
| **`itineraire[]`** | **`travel_program_days`** (1 ligne par étape) |
| **`dates_depart[]` datées** | **`departures`** (statut `draft`) |
| `hotels[]`, `supplements[]`, `images[]`, SEO, URLs | `logistics_meta` |

Volumes obtenus sur les 194 : **1018 jours de programme**, **333 départs datés**, 166 thèmes.

### Dates de départ

Les libellés d'origine sont hétérogènes. `App\Support\LegacyDateParser` ne crée un départ que
lorsqu'il extrait une date complète (jour + mois + année) :

- `Du 05 au 09 août 2026`, `19/01/2019 Au 24/01/2019`, `07 septembre 2026 (retour 19 septembre)` → départ daté ;
- `chaque samedi soir`, `Juin 2026`, `11 octobre au 17 octobre` (sans année) → **pas** de départ ;
- `22:30 : Départ de Tanger « Sahat Oumam »` → point de ramassage, **pas** une date.

Sur 456 lignes, 347 sont datées et 109 rejetées à raison. Les lignes non datées sont conservées
verbatim dans `logistics_meta.legacy_import.departs_non_dates`.

Les départs sont créés en **brouillon** avec une capacité nulle : ce sont des dates historiques,
pas des départs vendables. La contrainte unique `(voyage_id, start_date)` interdit les doublons.

### Images

Le seeder ne crée aucune ligne `voyage_images` : les URLs pointent encore vers `ajinsafro.ma` et
l'affichage casserait à la coupure du domaine. Les photos réelles (dossier `/static/team/`, les
assets de template sont écartés) sont listées dans
`logistics_meta.legacy_import.images_a_rapatrier`, et `images` reste dans la liste des manques.

Le rapatriement se fait ensuite avec :

```bash
php artisan legacy:import-images                    # simulation
php artisan legacy:import-images --execute --limit=10   # premier lot
php artisan legacy:import-images --execute              # le reste
```

**À lancer sur le serveur** : c'est son stockage qui reçoit les fichiers, et il doit pouvoir
joindre `ajinsafro.ma`. Volume : 401 fichiers distincts sur 135 programmes, ~120 Mo.

Chaque fichier est écrit sur le disque `public` sous `voyages/legacy/{legacy_id}/{n}.{ext}`, une
ligne `voyage_images` est créée, et la première photo devient la couverture si la fiche n'en a pas.
Le corps de la réponse est décodé avant écriture : une 404 ou une page HTML servie à la place d'une
image est rejetée. La commande est idempotente — elle retient les URLs déjà copiées dans
`images_importees` et ne retente que les échecs. `images` ne quitte la liste des manques que
lorsque toutes les photos d'une fiche sont passées.

L'extension vient du **contenu décodé**, pas de l'en-tête `Content-Type` : `ajinsafro.ma` annonce
`image/jpeg` pour tous ses fichiers, y compris ses PNG et ses WebP. Les rapatriements antérieurs à
ce correctif ont produit des fichiers mal nommés ; pour les remettre d'aplomb :

```bash
php artisan legacy:fix-image-extensions              # simulation
php artisan legacy:fix-image-extensions --execute    # renomme
```

Le renommage est reporté sur `voyage_images.path`, `voyages.featured_image` et
`legacy_import.images_importees`, de sorte qu'aucune référence ne reste orpheline. La commande est
idempotente et peut être relancée pour contrôler l'intégrité de la médiathèque historique.

### Rendre les photos visibles dans l'admin et sur le front

Rapatrier ne suffit pas : le catalogue admin et le thème Traveler lisent la vignette dans le meta
WordPress `_thumbnail_id`, jamais dans `voyages.featured_image`. Tant que les fichiers ne sont pas
déclarés comme *attachments*, les fiches importées restent sans image à l'écran.

```bash
php artisan legacy:publish-images-to-wp              # simulation
php artisan legacy:publish-images-to-wp --execute    # publie
```

Chaque photo est copiée sous `wp-content/uploads/{Y}/{m}/legacy-{legacy_id}-{n}.{ext}` — le nom
permet de retrouver le programme d'origine depuis la médiathèque WordPress —, un attachment est
créé, puis la vignette et la galerie (`_gallery`, `gallery`, `st_gallery`) sont posées sur le tour.

Les attachments créés sont mémorisés dans `legacy_import.wp_attachments` et vérifiés en base avant
réutilisation : un second passage ne duplique rien. Une vignette déjà valide est conservée, sauf
`--replace-thumbnail`. **Le statut éditorial des tours n'est pas touché** : une fiche en brouillon
le reste.

### Garder « À compléter » honnête

`completion.missing` est un instantané calculé au seed. Les traitements qui suivent
(`legacy:push-wp`, la saisie des agents) remplissent les trous sans le mettre à jour — c'est ainsi
que `lien_wordpress` restait affiché sur 105 fiches qui avaient pourtant leur tour WordPress.

```bash
php artisan legacy:refresh-missing              # simulation
php artisan legacy:refresh-missing --execute    # applique
```

Recalcule les clés vérifiables en base (`lien_wordpress`, `images`, `programme_jours`, `themes`,
`duree`, `prix`, `destination`, `prestations_incluses`) et conserve les autres telles quelles :
`departs_vendables`, `contenu_a_relire`, `extraction_partielle` et `url_publique` relèvent d'une
décision humaine ou de la qualité de l'extraction. Le recalcul est symétrique — un champ vidé
réapparaît dans la liste. `completion.status` n'est jamais modifié.

### Finaliser et publier

Les états d'étape du CRUD v2 (« x / 14 validées ») se lisent dans quatre magasins — métas
WordPress, tables `aj_tour_*` / `aj_travel_dates`, tables Laravel des vols, items, extras et
logistique — et non dans les colonnes Laravel. `legacy:finalize` écrit par les mêmes chemins que
le formulaire et complète chaque étape :

```bash
php artisan legacy:finalize                       # simulation
php artisan legacy:finalize --execute             # complète sans publier
php artisan legacy:finalize --execute --publish   # complète et publie
```

Données extraites d'abord — hôtels (catégorie et ville, **sans nom**), suppléments avec prix
analysés, `date_expiration` de l'ancienne offre comme date passée pour les fiches sans départ —
génériques et reconnaissables ensuite (« Hôtel 4 étoiles », « Vol à confirmer »). Pas de vol
fabriqué pour un voyage national en autocar. Couverture empruntée à une fiche de même destination
pour les fiches sans photo. Les lieux Traveler (`st_location`) sont créés par destination.

Rien n'est écrasé : chaque bloc n'est créé que s'il est absent, la commande est idempotente.
`--publish` passe le tour en `publish`, la fiche en `actif`, et pose `completion.status =
finalized` (le badge « À compléter » disparaît).

Le 2026-09-24, 193 fiches ont été finalisées et publiées ainsi ; restaient 14 fiches sans aucune
date (elles affichent « Date à confirmer ») et 6 sans aucune photo, faute de source.

Côté front, les dates passées restent listées sur la page de tour — désactivées, étiquetées
« Offre expirée », jamais présélectionnées — et les gestionnaires AJAX refusent toute date
antérieure au jour courant. Attention : la page de tour est rendue par
`templates/v1/single-st_tours.php`, pas par `templates/tour/partials/`.

## Changer de domaine

Le site a vocation à bouger deux fois : **`ajinsafro.net` → `ajinsafro.com`** maintenant, puis
**`ajinsafro.com` → `ajinsafro.ma`** une fois la société validée. La procédure ci-dessous vaut
pour les deux ; seules les valeurs changent.

L'hôte canonique retenu est **sans `www`**, comme aujourd'hui. Le back-office suit :
`booking.<domaine>`.

### Ce que le code fait déjà

Rien n'est figé sur un domaine :

- **Laravel** route par `PUBLIC_DOMAIN` / `ADMIN_DOMAIN` / `PARTNER_DOMAIN`, et les liens de
  l'admin passent par `config('app.public_url')` / `config('app.admin_url')`.
- **Les plugins WordPress** cherchent le back-office dans cet ordre : la constante
  `AJTH_LARAVEL_API_URL` (ou `AJTB_LARAVEL_API_URL`) de `wp-config.php`, l'option
  `ajinsafro_booking_url`, puis — à défaut — le sous-domaine `booking` de l'hôte servi. Sans rien
  configurer, un site sur `ajinsafro.com` appelle donc `booking.ajinsafro.com`.
- **Les anciennes URL du catalogue** (`/voyage-national/…`, `/voyages-international/…`,
  `/voyages-organisees/…`, `onedeal.php?id=`, `/team/buy.php?id=`) sont servies par
  `AJTB_Legacy_Permalinks`, quel que soit le domaine : le chemin ne change jamais.

### Liste de bascule, dans l'ordre

1. **DNS et certificat.** `ajinsafro.com` et `booking.ajinsafro.com` pointent sur le serveur ;
   certificat SSL émis pour les deux (AutoSSL cPanel) **avant** de basculer WordPress, sinon le
   site répond en erreur de certificat entre les deux étapes.

2. **Base WordPress.** Remplacer le domaine partout, sérialisation comprise — jamais en SQL brut :

   ```bash
   cd ~/public_html
   wp db export ~/sauvegarde-avant-bascule.sql          # filet de sécurité
   wp search-replace 'ajinsafro.net' 'ajinsafro.com' --all-tables-with-prefix --dry-run
   wp search-replace 'ajinsafro.net' 'ajinsafro.com' --all-tables-with-prefix
   wp option get siteurl && wp option get home           # doivent afficher https://ajinsafro.com
   wp cache flush && wp rewrite flush
   ```

   `--dry-run` annonçait 1348 remplacements au 2026-09-24. **Ne pas** lancer de remplacement sur
   `ajinsafro.ma` : ce domaine apparaît dans des adresses e-mail (`contact@ajinsafro.ma`) et dans
   les références au catalogue historique, qui doivent rester telles quelles.

3. **Laravel `.env`**, puis `php artisan config:cache` :

   ```
   APP_URL=https://ajinsafro.com
   PUBLIC_URL=https://ajinsafro.com
   FRONTEND_URL=https://ajinsafro.com
   PUBLIC_DOMAIN=ajinsafro.com
   ADMIN_URL=https://booking.ajinsafro.com
   ADMIN_DOMAIN=booking.ajinsafro.com
   PARTNER_URL=https://partenaire.ajinsafro.com
   PARTNER_DOMAIN=partenaire.ajinsafro.com
   WP_UPLOAD_URL=https://ajinsafro.com/wp-content/uploads
   SESSION_DOMAIN=.ajinsafro.com
   ```

   `SESSION_DOMAIN` est le piège : sans lui le cookie retombe sur l'hôte seul et la session
   partagée entre le back-office et le portail partenaire casse **en silence**.

4. **Redirections 301, chemin pour chemin**, depuis l'ancien domaine vers le nouveau —
   `ajinsafro.net` → `ajinsafro.com`, `booking.ajinsafro.net` → `booking.ajinsafro.com`. Remplace
   la règle `booking.ajinsafro.net → ajinsafro.net/` de `public/.htaccess` (fichier en
   `skip-worktree`, voir plus haut). Garder ces redirections en place **au moins un an**.

5. **Correctifs serveur hors git**, à refaire si l'hébergement change : `chmod 600 wp-config.php`,
   `.htaccess` d'`uploads/` interdisant PHP, aucune archive `.zip` dans `wp-content/plugins/`.

6. **Indexation.** `blog_public = 1` (Réglages → Lecture) — il est volontairement à 0 tant que le
   site n'est pas définitif. Vider le cache du sitemap Rank Math, vérifier que
   `st_tours-sitemap.xml` liste bien les chemins historiques, créer la propriété Search Console du
   nouveau domaine et y soumettre le sitemap.

7. **Search Console — changement d'adresse.** Depuis la propriété de l'ancien domaine, utiliser
   l'outil « Changement d'adresse » vers le nouveau. Il exige que les deux propriétés soient
   vérifiées et que les 301 du point 4 soient en place.

8. **Acceptation** : aucune des 368 anciennes URL ne doit tomber.

   ```bash
   php artisan legacy:check-urls --base=https://ajinsafro.com   # 0 défaut attendu
   ```

### Le cas d'ajinsafro.ma

Les 368 anciennes URL du catalogue viennent de `www.ajinsafro.ma`. Leur référencement ne se
transfère que si **le DNS d'`ajinsafro.ma` renvoie en 301, chemin pour chemin**, vers le domaine
servi. Tant que ce n'est pas fait, le travail sur les permaliens historiques ne sert qu'aux
visiteurs qui arrivent déjà sur le bon domaine.

Au moment de la bascule finale vers `.ma`, la même liste s'applique en sens inverse
(`ajinsafro.com` → `ajinsafro.ma`), et le point 7 se fait de `.com` vers `.ma`. Les anciennes URL
`.ma` redeviennent alors natives : plus aucune redirection inter-domaines pour elles.
## Ce que l'import ne crée jamais

Fichiers images, galeries, disponibilités réelles, chambres, vols, prix de vente actifs. Les départs
importés restent en brouillon à capacité nulle. Une fiche importée **n'est pas vendable** en
l'état — c'est voulu.

## Attention en cas de reprise partielle

Tant que `logistics_meta.completion.status` vaut `incomplete`, un nouveau passage du seeder
**réécrit le contenu et remplace les jours de programme** (c'est ce qui permet d'enrichir les 89
fiches déjà importées). Un agent qui reprend une fiche doit donc passer ce statut à autre chose
(p. ex. `complete`) pour la figer ; sinon son travail sur les jours serait perdu au prochain import.
Les départs, eux, ne sont jamais supprimés.

## Le lien vers l'ancien site dans l'admin

Chaque fiche importée porte un lien **« Ancien site ↗ »** vers sa page d'origine sur `ajinsafro.ma`,
pour comparer le contenu pendant la reprise :

- dans `Circuits > Voyages` (vue tableau et vue cartes), à côté du badge « À compléter » ;
- dans l'en-tête de la page d'édition v2, à côté de l'ID.

Il est calculé par `Voyage::legacySourceUrl()` : l'URL SEO quand elle existe, sinon la première
ancienne URL relevée (`/onedeal.php?id=…`). Les 194 programmes en ont un.

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

### 1 bis. Réparer le lien `_aj_laravel_voyage_id` (à faire avant toute publication)

```bash
php artisan wp:backfill-links            # simulation
php artisan wp:backfill-links --execute  # écrit
```

**Pourquoi c'est indispensable.** Le catalogue public
(`ajinsafro-traveler-home/templates/voyages.php`) contient un interrupteur tout ou rien : dès qu'au
moins **un** tour publié porte la meta `_aj_laravel_voyage_id`, la page se restreint aux tours qui la
portent. Tous les autres disparaissent.

Or cette meta n'était jamais écrite : `WpTourSyncService` passe par `WpRepository`, inopérant à cause
du double préfixe de table. Conséquence observée : publier un seul programme historique (poussé, lui,
avec la meta) faisait tomber le catalogue public de 13 voyages à 1. Remettre le programme en
brouillon les faisait réapparaître.

La commande pose la meta sur tous les tours WordPress déjà rattachés à un voyage Laravel
(`voyages.wp_post_id`), ce qui rétablit le catalogue complet. Elle signale aussi les tours publiés
sans voyage Laravel : ceux-là resteront masqués par le filtre, c'est le comportement voulu du plugin.

### 2. Fusionner les doublons

Quand l'offre existe **déjà** au catalogue (tour WordPress + voyage Laravel rattaché), il ne faut
pas créer un second tour : on reporte l'identité historique sur la fiche existante et on supprime
la fiche importée en double.

```bash
php artisan legacy:merge                                  # simulation
php artisan legacy:merge --execute                        # les cas sans ambiguïté
php artisan legacy:merge --execute --map=91:57 --map=466:80   # les cas à arbitrer
```

La fusion copie `legacy_import` et `seo` sur le voyage conservé, pose `_ajinsafro_legacy_id` sur son
tour WordPress, puis supprime la fiche importée. Elle **ne touche ni au contenu, ni au prix, ni au
slug** du voyage conservé, et ne pose pas de marque « À compléter » dessus : ce n'est pas un
brouillon. Une fiche importée portant des réservations n'est jamais supprimée.

Quand plusieurs voyages peuvent recevoir l'identité historique, la commande les liste avec le
`--map=<legacyId>:<voyageId>` à rejouer : c'est à l'agence de désigner la fiche de référence.

### 3. Publication

```bash
php artisan legacy:push-wp                       # simulation
php artisan legacy:push-wp --execute --limit=5    # premier lot
php artisan legacy:push-wp --execute              # le reste
php artisan legacy:push-wp --execute --adopt      # rattache aussi les doublons probables
php artisan legacy:push-wp --execute --id=43 --id=46
```

Sans `--adopt`, un programme dont le slug est proche d'un tour existant est **signalé et laissé de
côté** — aucun doublon n'est créé. Plusieurs tours candidats pour un même programme : la commande
refuse toujours, avec ou sans `--adopt`, plutôt que de choisir au hasard. Avec `--adopt`, le voyage
Laravel est rattaché au tour WordPress existant (`wp_post_id`, `_aj_laravel_voyage_id`,
`_ajinsafro_legacy_id`) sans en créer un second, et le slug WordPress en place est conservé. Un tour
déjà lié à un autre voyage Laravel n'est jamais repris — ce cas relève de `legacy:merge`.

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

### Le préfixe de chemin, côté WordPress

Le slug ne suffit pas : le post type `st_tours` est servi sous son rewrite natif (`/voyages/<slug>`),
alors que l'ancien site utilisait `/voyage-national/` et `/voyages-international/`.

`AJTB_Legacy_Permalinks` (plugin **ajinsafro-tour-bridge**) rejoue les deux anciens préfixes :

- une règle de réécriture résout `/voyage-national/<slug>` et `/voyages-international/<slug>` vers le
  tour correspondant ;
- `post_type_link` renvoie l'ancien chemin comme permalien du tour ;
- la redirection canonique de WordPress est neutralisée sur ces URLs, sinon elle renverrait vers
  `/voyages/<slug>`.

Le préfixe à utiliser est porté par le post, dans la meta `_aj_legacy_path_prefix`, écrite par
`legacy:push-wp` et `legacy:merge` depuis `logistics_meta.seo.legacy_path_prefix`. Un tour sans cette
meta garde le permalien natif : la réécriture ne change rien au reste du catalogue.

**Après déploiement du plugin, réactivez-le** (ou visitez Réglages > Permaliens) pour que les règles
de réécriture soient régénérées.

Sur les 89 programmes, 84 portent un préfixe historique (39 `voyage-national`,
45 `voyages-international`) ; les 5 sans URL SEO d'origine gardent le permalien natif.

### Une fiche importée renvoie 404 en public

C'est normal : les tours créés sont en **brouillon**. WordPress renvoie 404 à un visiteur non
connecté sur un brouillon. Pour prévisualiser, soyez connecté à l'admin WordPress ; la page ne
devient publique qu'une fois la fiche complétée et publiée.

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
