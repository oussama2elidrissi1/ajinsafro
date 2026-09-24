<?php

/*
|--------------------------------------------------------------------------
| Point d'entrée quand Laravel est servi dans un sous-dossier
|--------------------------------------------------------------------------
|
| Sous https://ajinsafro.com/booking/, l'application doit déduire son préfixe
| d'URL (/booking) pour router /booking/login comme /login. Elle le lit dans
| SCRIPT_NAME — que le gestionnaire PHP de cPanel, après une réécriture
| interne, ne renseigne pas toujours. On le fixe ici, à partir de la position
| réelle de ce dossier sous la racine du site, puis on délègue à public/.
|
| Hors de portée quand un sous-domaine pointe directement sur public/.
|
*/

$documentRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$prefix = ($documentRoot !== '' && str_starts_with(__DIR__, $documentRoot))
    ? substr(__DIR__, strlen($documentRoot))
    : '';

$_SERVER['SCRIPT_NAME'] = $prefix.'/index.php';
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);

require __DIR__.'/public/index.php';
