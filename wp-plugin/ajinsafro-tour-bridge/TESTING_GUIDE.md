# Guide de Test Rapide - Container Improvements

## 🧪 Tests à Effectuer

### 1. Test Desktop (≥1200px)

**Étapes :**
1. Ouvrir une page tour dans le navigateur
2. Redimensionner à 1920px ou plus
3. Vérifier :
   - ✅ Le contenu est centré
   - ✅ Il y a des marges visibles à gauche et à droite
   - ✅ Le contenu ne dépasse pas ~1200px de largeur
   - ✅ La sidebar est à droite (380px)
   - ✅ Le hero est centré avec les mêmes marges

**Résultat attendu :**
- Contenu confortable à lire
- Pas de texte qui s'étire sur toute la largeur
- Aspect professionnel "site moderne"

---

### 2. Test Tablet (768-1199px)

**Étapes :**
1. Redimensionner le navigateur à 1024px
2. Vérifier :
   - ✅ Layout passe à une colonne sous 1199px
   - ✅ Sidebar en dessous du contenu principal
   - ✅ Padding de 20-24px visible
   - ✅ Pas de scroll horizontal
   - ✅ Hero gallery en mode 1+2

**Résultat attendu :**
- Une seule colonne
- Tout le contenu reste lisible
- Aucun débordement

---

### 3. Test Mobile (<768px)

**Étapes :**
1. Redimensionner à 375px (iPhone)
2. Vérifier :
   - ✅ Contenu occupe bien l'écran
   - ✅ Padding de 16px
   - ✅ Pas de scroll horizontal
   - ✅ Hero gallery en mode slider
   - ✅ Searchbar responsive
   - ✅ Tabs navigation scrollable

**Tester aussi :**
- 320px (petit mobile)
- 414px (iPhone Plus)

**Résultat attendu :**
- Tout est lisible
- Navigation facile
- Pas de contenu coupé

---

## 🔍 Points de Contrôle Spécifiques

### Hero Section
- [ ] Image principale visible
- [ ] Breadcrumb lisible
- [ ] Titre bien formaté
- [ ] Meta info (durée, type) visible

### Search Bar
- [ ] 3 cartes horizontales (desktop)
- [ ] Stack vertical ou 2+1 (mobile)
- [ ] Champs select fonctionnels

### Programme/Itinéraire
- [ ] Cards des jours bien alignées
- [ ] Barres sticky alignées avec le contenu
- [ ] Pas de chevauchement

### Booking Box (Sidebar)
- [ ] Sticky sur desktop
- [ ] En dessous du contenu sur mobile
- [ ] Prix et CTA visible

---

## 🐛 Problèmes Potentiels et Solutions

### Problème : Contenu toujours full-width sur desktop
**Cause :** Le thème force un width avec !important
**Solution :** Vérifier dans les outils dev si des règles du thème surchargent
```css
/* Dans tour.css, ajouter si nécessaire */
.ajtb-tour-page .aj-wide-container {
    max-width: 1200px !important;
}
```

### Problème : Scroll horizontal sur mobile
**Cause :** Élément enfant avec width fixe
**Solution :** Vérifier les images, tables, ou éléments qui dépassent
```css
/* Forcer le respect du container */
.aj-wide-container img,
.aj-wide-container table {
    max-width: 100%;
    height: auto;
}
```

### Problème : Marges inconsistantes entre sections
**Cause :** Sections avec leur propre padding
**Solution :** Vérifier que toutes les sections utilisent `.aj-wide-container`

---

## 📱 Outils de Test Recommandés

### Navigateurs
- Chrome DevTools (Responsive Mode)
- Firefox Responsive Design Mode
- Safari Web Inspector

### Breakpoints Rapides
1. 1920px (Large Desktop)
2. 1440px (Desktop)
3. 1200px (Desktop Small)
4. 1024px (Tablet)
5. 768px (Tablet Small)
6. 375px (Mobile)
7. 320px (Small Mobile)

### Extensions Utiles
- **Responsive Viewer** : Tester plusieurs tailles en même temps
- **PerfectPixel** : Comparer avec une maquette
- **WhatFont** : Vérifier les tailles de police

---

## ✅ Checklist Finale

### Visuel
- [ ] Marges visibles sur desktop
- [ ] Contenu centré
- [ ] Pas de débordement sur mobile
- [ ] Images responsive
- [ ] Texte lisible à toutes les tailles

### Fonctionnel
- [ ] Navigation fonctionne
- [ ] Forms sont utilisables
- [ ] Boutons cliquables (assez grands sur mobile)
- [ ] Sticky sidebar fonctionne (desktop)
- [ ] Slider gallery fonctionne (mobile)

### Performance
- [ ] Pas de layout shift (CLS)
- [ ] Transitions fluides
- [ ] Images chargent correctement

---

## 🚀 Commandes Utiles

### Vider le cache WordPress
```php
// Dans wp-admin ou via plugin
wp_cache_flush();
```

### Recharger les CSS (sans cache)
- Chrome/Firefox : `Ctrl + Shift + R` ou `Cmd + Shift + R`
- Force reload complet

### Inspecter un élément
- Clic droit > Inspecter
- Vérifier les styles appliqués
- Chercher les règles surchargées

---

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifier les outils dev (erreurs console)
2. Comparer avec le fichier `tour.css` original
3. Tester avec le thème par défaut WordPress (Twenty Twenty-Three)
4. Vérifier les conflits de plugins

**Fichiers modifiés :**
- `assets/css/tour.css` (lignes ~58-250)
- Documentation : `CONTAINER_IMPROVEMENTS_SUMMARY.md`

Bonne chance avec les tests ! 🎉
