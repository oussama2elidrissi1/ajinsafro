# Chambres du départ dans la réservation rapide

Dans l’étape **Chambres & extras**, le bouton **Gérer les chambres du départ** ouvre une fenêtre utilisant les répartitions de `departure_room_allocations`, également utilisées par l’éditeur voyage. Il est réservé aux administrateurs disposant de `circuits.voyages.view`, comme l’éditeur voyage.

La fenêtre permet de modifier le type, la quantité, la capacité par chambre, le supplément par personne et l’hôtel concerné (ou le circuit complet), d’ajouter ou retirer un type et de générer des doubles avec une single pour une capacité impaire. La capacité du départ reste celle du départ ; le total couvert et l’écart se recalculent à la saisie. Une quantité de zéro désactive la disponibilité ; au moins une ligne doit être conservée.

Les valeurs valides sont enregistrées après 500 ms sans saisie, sans soumettre le dossier de réservation. Les écritures successives sont sérialisées. Le serveur retourne la nouvelle disponibilité, appliquée directement au formulaire : voyageurs, modes d’occupation et extras sont conservés, suppléments et total sont recalculés. Un type devenu indisponible est signalé et doit être remplacé avant de continuer. Un ancien chargement de disponibilité ne peut pas écraser cette mise à jour.

Les répartitions explicites du départ sont prioritaires sur les anciennes configurations d’hôtels. Les chambres consommées par les réservations sont déduites selon `booking_lifecycle.stock_consuming_statuses` et la politique de stock des options. Les dossiers déjà enregistrés conservent leurs montants. La fenêtre conserve les identifiants existants, interdit la suppression ou le changement de capacité/type/hôtel des lignes utilisées, et refuse une quantité inférieure aux chambres déjà utilisées. Les contrôles d’identifiants, les écritures et la disponibilité retournée sont atomiques. Une révision périmée retourne HTTP 409 et propose de recharger.

Routes GET et PUT : `admin/circuits/voyages/{voyage}/departures/{departure}/room-allocations`. Aucun changement de schéma n’est nécessaire.

## Vérifications locales

```powershell
C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/DepartureRoomAllocationEditorTest.php
C:/xampp/php/php.exe tests/Browser/render-departure-rooms.php "$env:TEMP/aji-departure-rooms-qa"
node tests/Browser/departure-rooms.mjs "$env:TEMP/aji-departure-rooms-qa"
```

Les tests HTTP utilisent SQLite en mémoire. Les contrôles Chrome utilisent les véritables vues Blade et scripts, avec un transport simulé, aux largeurs 390 et 1440 px. Ils vérifient notamment les écritures rapides, les erreurs serveur, le maintien des voyageurs et extras, les suppléments par personne, les libellés échappés, les ruptures de stock et le focus de la fenêtre. Les captures et le rapport JSON sont écrits dans le dossier temporaire indiqué.

Le contrôle complémentaire `ReservationDossierServiceTest` présente deux échecs préexistants : les tests attendent `unpaid`/`deposit`, tandis que le service existant retourne `non_paid`/`partial`. Ce service et ses tests n’ont pas été modifiés pour cette intervention.
