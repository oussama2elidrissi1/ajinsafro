# 🎯 Guide d'utilisation : Auto-injection Package Builder

## ✅ Fonctionnalité implémentée

Le plugin **Ajinsafro Core** peut maintenant afficher automatiquement le Package Builder sur toutes les pages de tours sans avoir besoin d'ajouter manuellement le shortcode.

---

## 📋 Fichiers modifiés/créés

### Fichiers modifiés :
1. ✅ `includes/Core/Options.php` - Ajout de 2 nouvelles options par défaut
2. ✅ `includes/Admin/Settings.php` - Ajout de sanitization pour les nouvelles options
3. ✅ `templates/admin/settings.php` - Ajout de l'UI pour les nouvelles options
4. ✅ `ajinsafro-core.php` - Initialisation de l'AutoInjector

### Fichiers créés :
5. ✅ `includes/Frontend/AutoInjector.php` - Classe d'auto-injection

---

## ⚙️ Configuration

### Options disponibles dans WordPress Admin

Allez à **WordPress Admin → Ajinsafro Core**

#### 1. **Auto-inject Package Builder** (checkbox)
- **Par défaut** : ✅ Activé (ON)
- **Description** : Active l'affichage automatique du Package Builder sur les pages de tours
- **Effet** : Quand activé, le Package Builder apparaît automatiquement sur toutes les pages `st_tours`

#### 2. **Auto-inject Position** (select)
- **Options** :
  - `After content` (par défaut) - Affiche le Package Builder après le contenu du tour
  - `Before content` - Affiche le Package Builder avant le contenu du tour
- **Description** : Choisissez où afficher le Package Builder par rapport au contenu

---

## 🧪 Tests recommandés

### Test 1 : Auto-injection activée (par défaut)

```
1. Aller dans WordPress Admin → Ajinsafro Core
2. Vérifier que "Auto-inject Package Builder" est coché ✅
3. Position : "After content"
4. Sauvegarder

5. Aller sur une page tour (ex: /tours/sejour-dubai/)
6. ✅ Le Package Builder doit s'afficher automatiquement APRÈS le contenu
7. ✅ Vérifier que les jours sont cliquables
8. ✅ Vérifier que le pricing s'affiche
```

**Résultat attendu :**
```
[Contenu du tour]
[Package Builder - Days + Pricing + Items]
```

---

### Test 2 : Position "Before content"

```
1. WordPress Admin → Ajinsafro Core
2. Changer "Auto-inject Position" à "Before content"
3. Sauvegarder

4. Rafraîchir la page tour
5. ✅ Le Package Builder doit maintenant s'afficher AVANT le contenu
```

**Résultat attendu :**
```
[Package Builder - Days + Pricing + Items]
[Contenu du tour]
```

---

### Test 3 : Anti-duplication (shortcode manuel)

```
1. Éditer un tour WordPress
2. Dans le contenu, ajouter manuellement : [aj_package_builder]
3. Sauvegarder

4. Afficher la page tour
5. ✅ Le Package Builder doit apparaître UNE SEULE FOIS (pas de duplication)
6. ✅ Il apparaît à l'endroit du shortcode (pas à la position auto-inject)
```

**Comportement :**
- Si le shortcode est présent dans le contenu → auto-injection désactivée automatiquement
- Le shortcode manuel a la priorité

---

### Test 4 : Désactiver l'auto-injection

```
1. WordPress Admin → Ajinsafro Core
2. Décocher "Auto-inject Package Builder"
3. Sauvegarder

4. Aller sur une page tour
5. ✅ Le Package Builder ne s'affiche PAS (sauf si shortcode manuel présent)
```

---

### Test 5 : Tour sans Laravel ID

```
1. Créer/éditer un tour WordPress
2. S'assurer que le meta _aj_laravel_voyage_id est vide ou absent
3. Sauvegarder

4. Afficher la page tour
5. ✅ Le Package Builder ne s'affiche PAS (même si auto-injection activée)
```

**Raison :** Le plugin vérifie la présence du `_aj_laravel_voyage_id` avant d'injecter.

---

## 🔧 Fonctionnement technique

### Conditions d'injection

Le Package Builder est injecté SEULEMENT si **TOUTES** les conditions sont remplies :

1. ✅ Option `auto_inject_builder` est activée
2. ✅ Pas en admin (`!is_admin()`)
3. ✅ Pas dans un flux RSS (`!is_feed()`)
4. ✅ Page single tour (`is_singular('st_tours')`)
5. ✅ Dans la boucle principale (`in_the_loop() && is_main_query()`)
6. ✅ Le contenu ne contient PAS déjà le shortcode `[aj_package_builder]`
7. ✅ Le tour a un `_aj_laravel_voyage_id` défini

### Anti-duplication

Le système vérifie :
- Présence du shortcode textuel : `[aj_package_builder`
- Présence du HTML déjà rendu : `aj-package-builder`

Si l'un des deux est détecté → pas d'injection automatique.

### Priorité du filtre

Le filtre `the_content` est appliqué avec priorité **20** :
- Permet aux autres plugins de modifier le contenu avant
- S'exécute après `wpautop` (priorité 10)
- S'exécute après `do_shortcode` (priorité 11)

---

## 📊 Cas d'usage

### Cas 1 : Site avec template uniforme
**Configuration recommandée :**
- ✅ Auto-inject : ON
- Position : After content
- **Résultat** : Tous les tours affichent le Package Builder automatiquement

### Cas 2 : Contrôle manuel par page
**Configuration recommandée :**
- ❌ Auto-inject : OFF
- **Utilisation** : Ajouter `[aj_package_builder]` manuellement où nécessaire

### Cas 3 : Mise en avant du Package Builder
**Configuration recommandée :**
- ✅ Auto-inject : ON
- Position : **Before content**
- **Résultat** : Le Package Builder apparaît en premier, attirant l'attention

---

## 🐛 Troubleshooting

### Package Builder ne s'affiche pas

**Vérifications :**
1. ✅ Option "Auto-inject" est activée dans settings
2. ✅ Vous êtes sur une page single `st_tours` (pas archive/liste)
3. ✅ Le tour a un meta `_aj_laravel_voyage_id` défini
4. ✅ L'URL Laravel est configurée dans settings
5. ✅ Aucun shortcode manuel dans le contenu

**Debug :**
```php
// Vérifier le meta
$laravel_id = get_post_meta(get_the_ID(), '_aj_laravel_voyage_id', true);
echo "Laravel ID: " . $laravel_id;

// Vérifier les options
$options = get_option('ajinsafro_settings');
var_dump($options['auto_inject_builder']);
```

### Package Builder apparaît 2 fois

**Cause probable :**
- Shortcode manuel + auto-injection activée

**Solution :**
1. Retirer le shortcode `[aj_package_builder]` du contenu
2. OU désactiver l'auto-injection

**Note :** Le plugin devrait normalement empêcher la duplication automatiquement.

### Position ne fonctionne pas

**Vérifications :**
1. ✅ Option "Auto-inject Position" est bien sauvegardée
2. ✅ Vider le cache WordPress (si plugin de cache actif)
3. ✅ Rafraîchir la page (Ctrl+F5)

---

## 💡 Recommandations

### Pour la majorité des cas :
```
✅ Auto-inject Package Builder : ON
📍 Auto-inject Position : After content
```

### Pour mise en avant maximale :
```
✅ Auto-inject Package Builder : ON
📍 Auto-inject Position : Before content
```

### Pour contrôle total par page :
```
❌ Auto-inject Package Builder : OFF
📝 Ajouter [aj_package_builder] manuellement
```

---

## 📝 Notes techniques

### Architecture
- **Séparation des préoccupations** : L'AutoInjector est une classe dédiée
- **Réutilisation** : Utilise `do_shortcode()` pour garantir le même rendu
- **Performance** : Filtre appliqué uniquement sur pages concernées
- **Compatibilité** : Compatible avec tous les themes/builders

### Code minimal et propre
- Aucune dépendance externe
- Utilise les hooks WordPress standards
- Respecte les best practices WP
- Compatible avec les systèmes de cache

---

## ✨ Résumé

### Avant cette feature :
- ❌ Nécessitait édition manuelle de chaque tour
- ❌ Nécessitait accès au template PHP
- ❌ Risque d'oubli sur certaines pages

### Après cette feature :
- ✅ Activation en 1 clic
- ✅ Fonctionne sur TOUS les tours automatiquement
- ✅ Changement global de position possible
- ✅ Compatible avec shortcode manuel (anti-duplication)
- ✅ Respect du meta `_aj_laravel_voyage_id`

---

**Version plugin :** 1.0.0  
**Feature ajoutée :** 2026-02-03  
**Status :** ✅ Production-ready
