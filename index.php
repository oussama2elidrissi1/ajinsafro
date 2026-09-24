<?php

/*
|--------------------------------------------------------------------------
| Point d'entrée quand Laravel est servi dans un sous-dossier
|--------------------------------------------------------------------------
|
| Sous https://ajinsafro.com/booking/, la requête arrive avec un SCRIPT_NAME en
| /booking/index.php : l'application en déduit son préfixe d'URL (/booking) et
| route /booking/login comme /login. Ce fichier ne fait que déléguer à public/,
| qui reste le vrai front controller.
|
| Hors de portée quand un sous-domaine pointe directement sur public/.
|
*/

require __DIR__.'/public/index.php';
