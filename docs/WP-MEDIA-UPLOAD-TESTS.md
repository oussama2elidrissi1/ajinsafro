# Tests manuels – Upload & affichage images (WordPress /booking)

## Prérequis

- `.env` : `APP_URL=https://votredomaine.com/booking` (sans slash final)
- Dossier physique : `public/wp-content/uploads` existe et est accessible en écriture
- En debug : `APP_DEBUG=true` pour voir les logs (path, URL, file_exists)

## 1. Upload logo

1. Aller dans **Admin → WordPress → Hotels** → modifier un hôtel.
2. Onglet **Hotel detail** → choisir un fichier (JPG/PNG/WebP, max 5 Mo) pour **Hotel logo**.
3. Enregistrer.
4. **Vérifier** : l’image s’affiche (pas “The image could not be loaded”) ; si le fichier est manquant côté serveur, le placeholder “Image introuvable” s’affiche.
5. **Optionnel** : ouvrir l’URL de l’image dans un nouvel onglet → l’URL doit être du type `https://votredomaine.com/booking/wp-content/uploads/2026/01/xxx.jpg`.

## 2. Upload galerie

1. Même page, onglet **Hotel detail** → section **Images / Gallery**.
2. Sélectionner plusieurs images (max 10, 5 Mo chacune).
3. Enregistrer.
4. **Vérifier** : les miniatures s’affichent ; pour chaque image manquante sur le disque, le placeholder “Image introuvable” s’affiche à la place.
5. Supprimer une image (bouton poubelle) → Enregistrer → la miniature disparaît de la liste.

## 3. Refresh & persistance

1. Après avoir uploadé logo + galerie, quitter la page puis revenir sur **Modifier** le même hôtel.
2. **Vérifier** : logo et galerie s’affichent toujours (URL construite via `_wp_attached_file`, pas `guid`).
3. Rafraîchir la page (F5) : les images restent affichées.

## 4. Ouvrir l’image dans un nouvel onglet

1. Clic droit sur une miniature (logo ou galerie) → “Ouvrir l’image dans un nouvel onglet”.
2. **Vérifier** : l’URL est correcte (base = `APP_URL` + `/wp-content/uploads/...`) et l’image s’affiche.

## 5. Bug /tmp (cPanel)

- Si avant correctif vous aviez : *The "/tmp/phpXXXX" file does not exist or is not readable* dans `uploadAndCreateAttachment()`.
- Après correctif : le mime est lu **avant** le déplacement du fichier (`getClientMimeType()` puis fallback extension) ; plus d’appel à `getMimeType()` après `move()`.
- Tester un upload logo + galerie : aucun message d’erreur lié au tmp.

## Logs debug (temporaire)

Avec `APP_DEBUG=true`, dans `storage/logs/laravel.log` après un upload vous devriez voir des lignes du type :

- `WordPressMediaService::uploadToWpUploads` : `app_url`, `relative_path`, `full_path`, `file_exists`
- `WordPressMediaService::uploadAndCreateAttachment` : `app_url`, `relative_path`, `full_path`, `final_url`, `file_exists`

Vérifier que `final_url` contient bien la base `/booking` si vous êtes sous ce chemin.
