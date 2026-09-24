<?php
/**
 * Plugin Name: Ajinsafro Booking Redirect
 * Description: Envoie le bouton « Réserver » de TravelerWP vers le parcours de réservation Laravel, avec les paramètres choisis.
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base du back-office Laravel, sans barre finale.
 *
 * Même résolution que les autres extensions : la constante de wp-config.php, l'option
 * d'administration, puis — à défaut — le sous-domaine `booking` de l'hôte servi. Rien n'est
 * figé sur un domaine : le site peut passer de .net à .com puis à .ma sans toucher au code.
 */
function ajbr_booking_base_url(): string
{
    if (function_exists('ajth_booking_base_url')) {
        return ajth_booking_base_url();
    }

    $url = '';
    foreach (['AJTH_LARAVEL_API_URL', 'AJTB_LARAVEL_API_URL'] as $constant) {
        if ($url === '' && defined($constant) && is_string(constant($constant))) {
            $url = (string) constant($constant);
        }
    }
    if ($url === '') {
        $url = (string) get_option('ajinsafro_booking_url', '');
    }
    if ($url === '') {
        $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        $host = strtolower((string) preg_replace('/^www\./', '', $host));
        $url = $host !== '' ? 'https://booking.' . $host : '';
    }
    $url = (string) preg_replace('#/api/?$#', '', trim($url));

    return rtrim((string) apply_filters('ajth_booking_base_url', $url), '/');
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'ajinsafro-booking-redirect',
        plugin_dir_url(__FILE__) . 'redirect.js',
        ['jquery'],
        '1.1.0',
        true
    );

    wp_localize_script('ajinsafro-booking-redirect', 'AJIN_BOOKING', [
        'base' => ajbr_booking_base_url() . '/booking/start',
    ]);
});
