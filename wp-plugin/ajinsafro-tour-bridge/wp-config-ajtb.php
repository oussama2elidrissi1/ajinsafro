<?php
/**
 * Plugin config: table des vols = même que Laravel (préfixe cFdgeZ_).
 * Modifier le préfixe si votre config Laravel utilise un autre (voir config/database.php connexion wp).
 */
if (!defined('ABSPATH')) {
    exit;
}
// Utiliser la table cFdgeZ_aj_tour_flights (préfixe Laravel)
define('AJTB_FLIGHTS_TABLE_PREFIX', 'cFdgeZ_');
