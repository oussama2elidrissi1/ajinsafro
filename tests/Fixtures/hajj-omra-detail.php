<?php

// Minimal WordPress rendering boundary. No application boot or database access.
define('ABSPATH', __DIR__);
define('AJTH_DIR', dirname(__DIR__, 2).'/wp-plugin/ajinsafro-traveler-home/');

function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_textarea($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function wp_unslash($value) { return $value; }
function home_url($path) { return $path; }
function current_time($format) { return '2026-09-10'; }
function selected($value, $current = true) { if ((string) $value === (string) $current) echo 'selected="selected"'; }
function checked($value, $current = true) { if ((string) $value === (string) $current) echo 'checked="checked"'; }
function disabled($value, $current = true) { if ((string) $value === (string) $current) echo 'disabled="disabled"'; }
function wp_nonce_field($action, $name) { echo '<input type="hidden" name="'.esc_attr($name).'" value="fixture-nonce">'; }
function ajth_get_hajj_omra_detail_url($slug) { return '/hajj-omra/'.$slug.'/'; }

class HajjOmraDetailFixture
{
    public static function render(array $overrides = [], array $post = [], string $error = ''): string
    {
        $previousPost = $_POST;
        $_POST = $post;
        $current_package = array_replace([
            'title' => 'Omra Ramadan 15 jours', 'type_label' => 'Ramadan', 'status' => 'published',
            'slug' => 'omra-ramadan', 'detail_url' => '/hajj-omra/omra-ramadan/',
            'short_description' => 'Formule Ramadan avec encadrement, hôtels proches et programme spirituel structuré.',
            'description' => 'Une formule Omra Ramadan complète au départ du Maroc avec accompagnement Ajinsafro, séjour à Médine puis à Makkah et organisation fluide sur tout le parcours.',
            'departure_city' => 'Casablanca', 'duration_label' => '15 jours / 14 nuits',
            'price_from' => 21900, 'child_price' => null, 'currency' => 'DH', 'remaining_places' => 14,
            'makkah_hotel' => 'Emaar Al Khalil', 'makkah_haram_distance' => '850 m',
            'madinah_hotel' => 'Saja Al Madinah', 'madinah_haram_distance' => '400 m',
            'meal_plan_label' => 'Petit-déjeuner', 'transport_included' => true, 'visa_included' => true,
            'guidance_included' => true, 'room_type' => 'quadruple',
            'included_items' => ['Vol aller-retour', 'Visa Omra', 'Hôtels Makkah et Madinah', 'Transferts internes', 'Encadrement Ajinsafro'],
            'excluded_items' => ['Dépenses personnelles', 'Repas hors formule', 'Assurances complémentaires'],
            'required_documents' => 'Passeport valide, photos d’identité, copie CIN.',
            'booking_conditions' => 'Acompte de 30 % à la confirmation. Solde avant départ.',
            'departures' => [
                ['departure_date' => '2026-12-05', 'return_date' => '2026-12-19', 'status' => 'published', 'remaining_places' => 10, 'price_from' => 22500],
                ['departure_date' => '2026-11-28', 'return_date' => '2026-12-12', 'status' => 'published', 'remaining_places' => 14, 'price_from' => 21900],
            ],
            'room_prices' => [
                ['room_type' => 'double', 'room_type_label' => 'Chambre double', 'stock' => 8, 'price' => 24500],
                ['room_type' => 'quadruple', 'room_type_label' => 'Chambre quadruple', 'stock' => 20, 'price' => 21900],
                ['room_type' => 'triple', 'room_type_label' => 'Chambre triple', 'stock' => 12, 'price' => 22900],
                ['room_type' => 'single', 'room_type_label' => 'Chambre single', 'stock' => 2, 'price' => 27900],
            ],
            'program_days' => [
                ['day_number' => 1, 'title' => 'Départ du Maroc', 'city' => 'Casablanca', 'description' => 'Convocation aéroport, formalités et vol vers l’Arabie Saoudite.'],
                ['day_number' => 2, 'title' => 'Installation à Médine', 'city' => 'Madinah', 'description' => 'Accueil, transfert hôtel et premiers repères du séjour.'],
                ['day_number' => 6, 'title' => 'Transfert vers Makkah', 'city' => 'Makkah', 'description' => 'Trajet organisé avec assistance et installation à l’hôtel.'],
                ['day_number' => 15, 'title' => 'Retour', 'city' => 'Jeddah', 'description' => 'Check-out, transfert aéroport et vol retour.'],
            ],
        ], $overrides);
        $current_slug = 'omra-ramadan';
        $fallback_image = '/wp-plugin/ajinsafro-traveler-home/assets/images/fallback-hajj-omra.svg';
        $page_url = '/hajj-omra/';
        $posted_room_type = $post['room_type'] ?? '';
        $posted_departure = $post['selected_departure_date'] ?? '';
        $success_message = '';
        $error_message = $error;
        $status_badge = static fn ($package) => ['class' => 'is-available', 'label' => 'Disponible'];
        $format_price = static fn ($price, $currency = 'DH') => is_numeric($price) ? number_format((float) $price, 0, ',', ' ').' '.$currency : 'Sur demande';
        $format_date = static fn ($date) => $date ? date('d M Y', strtotime($date)) : 'À confirmer';
        $whatsapp_link = static fn ($package) => 'https://wa.me/212660683464?text='.rawurlencode($package['title']);
        ob_start();
        try {
            include AJTH_DIR.'templates/partials/hajj-omra-detail.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
            $_POST = $previousPost;
        }
    }
}
