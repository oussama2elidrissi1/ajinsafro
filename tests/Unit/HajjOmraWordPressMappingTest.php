<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class HajjOmraWordPressMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require __DIR__.'/../Fixtures/hajj-omra-wordpress.php';
        $GLOBALS['ho_wp_offer'] = [
            'id' => 2, 'slug' => 'omra-ramadan', 'title' => 'Omra Ramadan', 'title_fr' => 'Omra Ramadan', 'title_ar' => 'عمرة رمضان',
            'type' => 'omra', 'type_label' => 'Omra', 'status' => 'published', 'currency' => 'DH', 'price_from' => 14900,
            'remaining_places' => 30, 'duration_days' => 14, 'duration_nights' => 13, 'duration_label' => '14 jours / 13 nuits',
            'departure_city' => 'Casablanca', 'departures' => [], 'has_formulas' => true,
            'formulas' => [['id' => 3, 'name_fr' => 'Touristique', 'name_ar' => 'السياحي', 'departure_id' => null,
                'accommodations' => [['id' => 4, 'city' => 'madinah', 'name' => 'Hôtel Médine', 'name_ar' => 'فندق المدينة']],
                'prices' => ['double' => ['id' => 9, 'tariff_id' => 9, 'room_type' => 'double', 'room_type_label' => 'Chambre double',
                    'room_type_label_ar' => 'الثنائي', 'price' => 14900, 'stock' => 10]],
            ]],
        ];
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_GET = []; $_POST = [];
        $GLOBALS['ho_wp_slug'] = ''; $GLOBALS['ho_wp_reads'] = [];
    }

    private function render(): string
    {
        ob_start();
        try { include AJTH_DIR.'templates/hajj-omra.php'; return ob_get_contents(); }
        finally { ob_end_clean(); }
    }

    public function test_catalog_localizes_the_cached_payload_and_preserves_language_in_detail_urls(): void
    {
        $_GET['lang'] = 'ar';
        $html = $this->render();
        $this->assertStringContainsString('عمرة رمضان', $html);
        $this->assertStringContainsString('14 900 DH', $html);
        $this->assertStringContainsString('عروض الحج والعمرة المتاحة', $html);
        $this->assertStringContainsString('/hajj-omra/omra-ramadan/?lang=ar', $html);
        $this->assertSame('ajth_hajj_omra_packages_v1', $GLOBALS['ho_wp_reads'][0][1]);
        $_GET['lang'] = 'fr';
        $this->assertSame('Omra Ramadan', ajth_prepare_hajj_omra_package_payload($GLOBALS['ho_wp_offer'])['title']);
        $this->assertSame('Omra Ramadan', $GLOBALS['ho_wp_offer']['title']);
    }

    public function test_wordpress_posts_relational_booking_ids_to_the_existing_laravel_endpoint(): void
    {
        $_GET['lang'] = 'ar'; $GLOBALS['ho_wp_slug'] = 'omra-ramadan'; $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['ajth_hajj_omra_booking_request' => '1', 'ajth_hajj_omra_nonce' => 'fixture-nonce',
            'full_name' => 'عميل', 'phone' => '0600000000', 'email' => 'guest@example.com', 'adults' => 1,
            'formula_id' => '3', 'tariff_id' => '9', 'departure_id' => '7', 'room_type' => 'double'];
        $html = $this->render();
        [$url, $options] = $GLOBALS['ho_wp_post'];
        $this->assertSame('https://api.test/api/public/hajj-omra/packages/omra-ramadan/booking-requests', $url);
        $this->assertSame(3, $options['body']['formula_id']);
        $this->assertSame(9, $options['body']['tariff_id']);
        $this->assertSame(7, $options['body']['departure_id']);
        $this->assertSame('ar', $options['body']['locale']);
        $this->assertStringContainsString('تم تسجيل طلبكم.', $html);
        $this->assertStringContainsString('data-formula-id="3"', $html);
    }
}
