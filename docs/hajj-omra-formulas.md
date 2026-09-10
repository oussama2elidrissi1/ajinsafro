# Formules commerciales Hajj & Omra

Implémentation du 10 septembre 2026. Le module et la fiche moderne existants sont conservés. Les formules utilisent les hébergements, tarifs, départs et jours du programme de la même offre.

## 1. Architecture actuelle trouvée

Laravel gère les données métier et l’éditeur en huit étapes. Les contrôleurs publics exposent `/api/public/hajj-omra/packages` et `/api/public/hajj-omra/packages/{slug}`. Le plugin **ajinsafro-traveler-home** consomme ces endpoints dans `includes/hajj-omra-catalog.php`, puis affiche `/hajj-omra/` et `/hajj-omra/{slug}/`.

Il n’y a pas de lecture SQL directe ni de tables miroir WordPress pour ce catalogue. Les réponses JSON sont conservées dans les transients existants : 300 secondes pour le catalogue, 180 secondes pour une fiche. L’enregistrement et la suppression Laravel déclenchent déjà leur invalidation via `WpCatalogCacheInvalidator`.

L’analyse a porté sur les migrations, modèles, services, contrôleurs, vues et helpers du plugin. La connexion au serveur MySQL local a été refusée : le schéma effectif d’une base déployée n’a pas été consulté. Aucune migration n’a été appliquée à cette base pendant le travail.

## 2–4. Tables, relations et éléments réutilisés

| Table existante | Relation et réutilisation |
|---|---|
| `hajj_omra_packages` | Offre, traductions, publication, durée, devises et champs historiques |
| `hajj_omra_package_hotels` | Hébergements appartenant à l’offre : ville, nom, catégorie, distance, nuits, pension, photo, description |
| `hajj_omra_room_prices` | Tarifs de l’offre : type de chambre du catalogue existant, montant, ancien prix, capacité, stock, activation |
| `hajj_omra_departures` | Départs de l’offre : dates, disponibilité, places ; identifiants conservés lors des modifications |
| `hajj_omra_program_days` | Programme et traductions ; un séjour peut référencer un jour existant |
| `hajj_omra_service_items` | Inclus/exclusions FR/AR |
| `hajj_omra_package_images` | Galerie existante |
| `hajj_omra_booking_requests` | Demandes et relation au départ existantes |

Les miroirs historiques d’hôtels et de prestations restent synchronisés automatiquement pour les anciens consommateurs. Ils ne créent aucune nouvelle saisie manuelle. L’éditeur d’hébergement conserve maintenant **tous** les hôtels d’une ville : l’ancien `keyBy('city')` pouvait en masquer puis en supprimer au prochain enregistrement.

## 5–7. Ajouts, migration et modèles

Migration : `database/migrations/2026_09_10_130000_add_hajj_omra_commercial_formulas.php`.

| Ajout | Contenu |
|---|---|
| `hajj_omra_formulas` | FK `package_id`, FK `departure_id` nullable, `name_fr`, `name_ar`, descriptions FR/AR, ordre, activation, timestamps |
| `hajj_omra_formula_hotels` | FK formule + FK hôtel, FK jour du programme nullable, `nights_override` nullable, ordre ; unicité formule/hôtel |
| `hajj_omra_formula_prices` | FK formule + FK tarif, ordre ; unicité formule/tarif ; **aucun montant recopié** |
| Hôtels existants | `name_ar` et `location_ar` nullables |
| Demandes existantes | `formula_id` et `tariff_id` nullables, avec FK |

`HajjOmraFormula` appartient à une offre et éventuellement à un départ, possède des séjours et est reliée aux tarifs par pivot. `HajjOmraFormulaHotel` relie un hôtel existant et éventuellement un jour du programme. Les relations inverses utiles à l’offre et à la demande sont ajoutées aux modèles existants.

La suppression d’une offre cascade aux formules ; les pivots suivent les suppressions des sources. Les références des demandes deviennent nulles si une formule ou un tarif est supprimé. Une réaffectation administrative de la demande à une autre offre efface les anciennes liaisons formule/tarif/départ.

Les nouvelles colonnes sont compatibles avec les anciennes offres. La nouvelle table de textes utilise explicitement `utf8mb4_unicode_ci` ; les connexions MySQL du projet utilisent déjà `utf8mb4`. Les migrations ont été exécutées par les tests sur SQLite en mémoire. Le charset des tables MySQL effectivement déployées devra être contrôlé lors du déploiement.

## 8. CRUD

La section **Formules commerciales** se trouve dans **Tarifs**. Elle permet d’ajouter, modifier, désactiver et supprimer plusieurs formules ; de choisir leurs hébergements, tarifs et départ ; de renseigner les noms/descriptions FR/AR et l’ordre.

- Les listes lisent les lignes actuelles de l’éditeur, y compris les sources qui ne sont pas encore enregistrées.
- Les références temporaires `new:<client_key>` survivent au réordonnancement et à la validation. Le service les résout en IDs de cette offre dans la transaction d’enregistrement. Elles ne sont pas stockées dans les tables métier.
- Une source retirée reste signalée dans le sélecteur pour provoquer une validation explicite, au lieu d’être remplacée silencieusement.
- Une formule active publiée exige un nom FR **ou** AR, au moins un hébergement et au moins un tarif actif. Une formule incomplète reste enregistrable en brouillon.
- Un seul tarif par type de chambre peut être lié à une même formule. Des formules différentes peuvent utiliser des tarifs différents pour un même type.
- Les identifiants étrangers à l’offre, les données mal formées et les dates de départ dupliquées sont rejetés avec des erreurs de validation. L’enregistrement est transactionnel.
- Le champ principal « à partir de » affiche le prix calculé et devient non saisissable lorsqu’un catalogue tarifaire est présent. Le champ historique reste disponible pour les offres sans tarif.
- Le sélecteur global FR/AR change les champs, les libellés et la direction de l’éditeur ; les entrées numériques et les dates restent LTR. Un titre uniquement arabe est accepté.

**Prévisualiser le tableau client enregistré** ouvre l’aperçu existant à la section formules et conserve la langue choisie. Il faut enregistrer le brouillon avant d’y voir les dernières modifications. L’aperçu et WordPress appellent le **même renderer PHP** et utilisent le **même CSS**.

## 9. Plugin WordPress

Le helper existant est étendu ; aucun second catalogue, repository SQL ou template complet indépendant n’est créé. Le détail moderne accueille la section **Formules & hébergements** à la place des cartes tarifaires génériques lorsqu’une offre possède des formules.

Le renderer `includes/hajj-omra-commercial-table.php` regroupe les hôtels par ville, affiche plusieurs hôtels dans une même cellule si nécessaire et génère les colonnes depuis les types réellement liés. Il accepte une seule ville, un seul tarif, des hôtels sans photo/catégorie et des durées différentes par formule.

À plus de 900 px, chaque formule utilise un tableau. À 900 px et moins, les cellules deviennent des cartes d’hébergement puis des lignes de prix, sans défilement horizontal du tableau. Les colonnes suivent naturellement la direction RTL en arabe. Les montants et les dates sont isolés avec `bdi`.

`?lang=fr` et `?lang=ar` sélectionnent la langue ; à défaut, le plugin utilise la locale WordPress. Les deux langues restent dans le même cache JSON. La traduction et les replis FR↔AR sont appliqués après lecture du cache. Les noms arabes, programmes, inclus/exclusions, documents et conditions proviennent de Laravel. Aucun contenu commercial supplémentaire n’est à saisir dans WordPress.

## 10. Mapping Laravel → WordPress et réservation

`HajjOmraCommercialPresenter` produit le contrat partagé par l’API et l’aperçu. Les anciennes clés sont conservées et enrichies :

```text
offre
  title, title_fr, title_ar, descriptions, documents, conditions…
  price_from, currency, has_formulas
  departures[] : id, dates, statut, places, price_from calculé
  hotels[] : id, ville, nom/nom_ar, localisation/localisation_ar…
  room_prices[] : id, tariff_id, type, libellés FR/AR, montant, activation…
  program_days[] : id, numéro, titres/descriptions FR/AR…
  formulas[]
    id, name_fr, name_ar, descriptions, departure_id, dates, ordre
    accommodations[]
      id (hôtel), stay_id, ville, informations de l’hôtel
      program_day_id, nights, start_day, stay_start_date, stay_end_date
    prices{type_de_chambre}
      tariff_id, room_type, libellés FR/AR, price, old_price, stock…
```

**Source du prix :** `hajj_omra_room_prices.price`. Le prix global est le minimum des tarifs actifs des formules actives et complètes. Sans aucune formule, il vient des tarifs actifs de l’offre. Sans aucun tarif, les valeurs historiques de l’offre et des départs publiés restent disponibles. Si toutes les formules ou tous les tarifs sont désactivés, le prix devient « sur demande » ; les tarifs non liés ne réapparaissent pas dans le tableau.

Le prix d’un départ provient des formules applicables à ce départ, y compris celles sans restriction de départ. Les montants historiques des départs ne remplacent pas une source tarifaire existante. Le catalogue public liste les offres publiées ou complètes ; les offres expirées restent consultables par leur ancienne URL mais sortent du catalogue.

**Dates :** le premier séjour commence au départ lié. Les suivants suivent l’ordre des séjours et leurs nuits. Un jour de programme lié donne explicitement le décalage depuis le départ. Les nuits viennent de l’hôtel, sauf durée spécifique renseignée sur le séjour. Si une durée précédente manque, les dates suivantes restent inconnues jusqu’à un jour du programme explicite. Une formule sans départ lié n’affiche pas de dates fixes. Il n’y a ni dates recopiées ni conversion hégirienne approximative.

**Réservation :** le sélecteur de formule filtre les tarifs et les départs, actualise l’estimation et transmet `formula_id`, `tariff_id` et `departure_id`. Laravel contrôle leur appartenance commune, l’activation, le stock du tarif et la disponibilité du départ. Il déduit le type de chambre depuis le tarif ; un montant envoyé par le navigateur n’est jamais utilisé. Les anciennes demandes sans formule restent acceptées pour les offres historiques. Il s’agit toujours d’une demande de contact, sans paiement ni blocage de stock.

**Performance/cache :** chargement anticipé des relations, une récupération JSON structurée côté plugin, aucune requête par cellule. Les clés de cache existantes et leur invalidation sont conservées. Sans secret d’invalidation configuré, les TTL existants déterminent le délai de mise à jour ; les éventuels caches HTML/CDN sont à purger selon le déploiement existant.

## 11. Vérifications

Commande PHP :

```powershell
php artisan test --filter='HajjOmraCommercialFormulaTest|HajjOmraOfferEditorTest|HajjOmraDetailTemplateTest'
```

Résultat : **29 tests réussis, 236 assertions**. Scénarios couverts : une et deux formules ; chambres double/triple/quadruple et quintuple ; une seule ville ; plusieurs hôtels dans la même ville ; FR seul et AR seul ; nuits spécifiques et dates dérivées ; modification des prix/noms ; désactivation ; minima sans tarifs non liés ; demandes avec IDs cohérents ; refus des liaisons invalides ; rollback ; absence de formules ; stabilité des anciens identifiants ; invalidation des caches ; nombre de requêtes constant pour un puis trois voyages ; égalité du HTML du tableau dans l’aperçu ; échappement HTML et JSON contre l’injection.

Vérification navigateur reproductible, avec Chrome installé et PHP/Node disponibles :

```powershell
php tests/Browser/render-hajj-omra-formulas.php "$env:TEMP/ajod-formulas-qa"
node tests/Browser/hajj-omra-formulas.mjs "$env:TEMP/ajod-formulas-qa"
```

Le générateur refuse toute base autre que SQLite en mémoire. Il produit des pages avec les vues/CSS/JS réels et le payload de l’API. Le script ouvre un Chrome headless isolé, vérifie FR/AR aux largeurs **390, 800 et 1440 px**, puis l’éditeur : table/cartes, absence de débordement, ordre RTL, prix, IDs, changement de formule/départ/chambre, champs requis, langues, nouvelles sources avant enregistrement, réindexation, ajout/suppression. Les captures PNG et le résultat JSON sont placés dans le dossier passé en argument. Les captures arabe desktop/mobile et de l’éditeur ont été inspectées visuellement.

Les vérifications de syntaxe PHP et JavaScript ont également réussi. Aucun test ni changement n’a été effectué sur le WordPress de production.

## 12. Déploiement et risques de régression

1. Sauvegarder la base selon le processus habituel, remettre MySQL en service et vérifier l’état des migrations/charsets de l’environnement cible.
2. Déployer ensemble les changements Laravel et la migration, puis exécuter `php artisan migrate --force` et `php artisan view:clear`.
3. Inclure sur le serveur Laravel le dossier `wp-plugin/ajinsafro-traveler-home/includes/` et le CSS des formules : l’aperçu y lit le composant partagé.
4. Mettre à jour le plugin existant avec ses fichiers modifiés et nouveaux. Aucun changement de permalien n’est nécessaire.
5. Vérifier la configuration d’invalidation WordPress existante ; purger les caches externes éventuels.
6. Créer un brouillon avec une formule, prévisualiser les deux langues, puis publier selon le processus habituel.

La migration ne supprime ni ne convertit les anciennes offres. Les sources et les formules sont mises à jour par ID. Un ancien client qui omet les formules ne peut pas retirer silencieusement une de leurs sources liées. Les anciennes fiches sans formule conservent leur présentation.

Les changements intentionnels sont : exclusion des tarifs inactifs du minimum, priorité des tarifs liés sur les montants historiques, titre FR ou AR, retrait des offres expirées du catalogue et retrait des notes internes du JSON public. La suppression explicite d’une formule/tarif annule sa FK dans les demandes historiques ; elle ne conserve pas un instantané commercial de la formule supprimée. Pour les offres très volumineuses, dimensionner `max_input_vars` du serveur PHP pour l’ensemble du formulaire répétable.

## 13. Fichiers de cette implémentation

La liste ci-dessous exclut les modifications simultanées de menus, rôles, utilisateurs et journal Laravel, qui ne font pas partie de ce travail.

<!-- FILE_LIST -->
