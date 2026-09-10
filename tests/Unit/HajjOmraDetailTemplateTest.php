<?php

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class HajjOmraDetailTemplateTest extends TestCase
{
    public function test_formula_table_uses_dynamic_room_types_fallback_languages_and_safe_json(): void
    {
        $formula = ['id' => 3, 'name_fr' => 'Séjour Médine', 'name_ar' => '', 'description_fr' => '</script><script>alert(1)</script>',
            'departure_id' => null, 'prices' => ['quintuple' => ['id' => 9, 'tariff_id' => 9, 'room_type' => 'quintuple',
                'room_type_label' => 'Chambre quintuple', 'room_type_label_ar' => 'الخماسي', 'price' => 12000, 'stock' => 10]],
            'accommodations' => [['id' => 4, 'city' => 'madinah', 'name' => 'Hôtel Médine', 'name_ar' => 'فندق المدينة', 'nights' => 4]]];
        $_GET['lang'] = 'ar';
        $html = \HajjOmraDetailFixture::render(['has_formulas' => true, 'formulas' => [$formula], 'price_from' => 12000]);
        $document = $this->document($html);
        $this->assertSame('rtl', $document->evaluate('string(//article/@dir)'));
        $this->assertSame('ar', $document->evaluate('string(//article/@lang)'));
        $this->assertSame(1, $document->query('//section[@data-formula-id="3"]')->length);
        $this->assertStringContainsString('الخماسي', $html);
        $this->assertStringContainsString('فندق المدينة', $html);
        $this->assertStringContainsString('Séjour Médine', $html);
        $this->assertSame(2, $document->query('//table[contains(@class,"ajho-formula__table")]//th')->length);
        $this->assertSame(0, $document->query('//script[not(@type="application/json")]')->length);
        $json = $document->evaluate('string(//script[@data-formulas-json])');
        $this->assertSame($formula['description_fr'], json_decode($json, true, 512, JSON_THROW_ON_ERROR)[0]['description_fr']);
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertSame('9', $document->evaluate('string(//input[@name="tariff_id"]/@value)'));
        $this->assertSame(0, $document->query('//div[contains(@class,"ajod-room ")]')->length);
    }

    public function test_disabled_formulas_do_not_show_legacy_prices_or_selectable_tariffs(): void
    {
        $html = \HajjOmraDetailFixture::render(['has_formulas' => true, 'formulas' => [], 'price_from' => null]);
        $document = $this->document($html);
        $this->assertStringContainsString('Aucune formule disponible pour le moment.', $html);
        $this->assertSame(1, $document->query('//select[@id="ajho-room-type"]/option')->length);
        $this->assertSame('', $document->evaluate('string(//input[@name="tariff_id"]/@value)'));
        $this->assertSame('Sur demande', $document->evaluate('string(//output[@data-estimate])'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        require __DIR__.'/../Fixtures/hajj-omra-detail.php';
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    public function test_default_departure_and_room_match_the_form_and_summary(): void
    {
        $document = $this->document(\HajjOmraDetailFixture::render());
        $this->assertSame('2026-11-28', $document->evaluate('string(//select[@id="ajho-departure"]/option[@selected]/@value)'));
        $this->assertSame('2026-11-28', $document->evaluate('string(//input[@data-departure-choice and @checked]/@value)'));
        $this->assertSame('quadruple', $document->evaluate('string(//select[@id="ajho-room-type"]/option[@selected]/@value)'));
        $this->assertSame('21 900 DH', $document->evaluate('string(//output[@data-estimate])'));
        $this->assertSame('28 Nov 2026', $document->evaluate('string(//*[@data-summary-date])'));
        $this->assertSame(6, $document->query('//nav[@aria-label="Sections de l’offre"]//a')->length);
        $this->assertSame(1, $document->query('//input[@name="ajth_hajj_omra_nonce"]')->length);
    }

    public function test_unavailable_departures_and_rooms_cannot_be_selected(): void
    {
        $document = $this->document(\HajjOmraDetailFixture::render([
            'departures' => [
                ['departure_date' => '2026-10-01', 'status' => 'full', 'remaining_places' => 0],
                ['departure_date' => '2026-08-01', 'status' => 'published', 'remaining_places' => 10],
                ['departure_date' => '2026-12-01', 'status' => 'draft', 'remaining_places' => 10],
            ],
            'room_prices' => [['room_type' => 'single', 'price' => 100, 'stock' => 0]],
        ]));
        $this->assertSame(3, $document->query('//input[@data-departure-choice and @disabled]')->length);
        $this->assertSame(0, $document->query('//select[@id="ajho-departure"]/option[@value!=""]')->length);
        $this->assertSame(1, $document->query('//select[@id="ajho-room-type"]/option[@value="single" and @disabled]')->length);
        $this->assertSame(0, $document->query('//input[@data-departure-choice and @checked]')->length);
    }

    public function test_form_errors_preserve_choices_and_escape_user_content(): void
    {
        $html = \HajjOmraDetailFixture::render([], [
            'full_name' => '"><script>alert(1)</script>', 'message' => '</textarea><script>alert(2)</script>',
            'selected_departure_date' => '2026-12-05', 'room_type' => 'double', 'adults' => 2, 'children' => 1,
        ], 'Vérifiez votre email.');
        $document = $this->document($html);
        $this->assertSame(0, $document->query('//script[not(@type="application/json")]')->length);
        $this->assertSame('double', $document->evaluate('string(//select[@id="ajho-room-type"]/option[@selected]/@value)'));
        $this->assertSame('2026-12-05', $document->evaluate('string(//select[@id="ajho-departure"]/option[@selected]/@value)'));
        $this->assertSame('49 000 DH', $document->evaluate('string(//output[@data-estimate])'));
        $this->assertStringContainsString('Tarif enfants à confirmer', $html);
        $this->assertSame('Vérifiez votre email.', $document->evaluate('string(//*[@role="alert"])'));
    }

    public function test_known_child_prices_are_included_and_empty_offers_render_without_warnings(): void
    {
        $document = $this->document(\HajjOmraDetailFixture::render(['child_price' => 1000], ['adults' => 2, 'children' => 2]));
        $this->assertSame('45 800 DH', $document->evaluate('string(//output[@data-estimate])'));

        $html = \HajjOmraDetailFixture::render([
            'price_from' => null, 'departures' => [], 'room_prices' => [], 'gallery' => [],
            'program_days' => [], 'included_items' => [], 'excluded_items' => [],
            'required_documents' => null, 'booking_conditions' => null,
        ]);
        $document = $this->document($html);
        $this->assertSame('Sur demande', $document->evaluate('string(//output[@data-estimate])'));
        $this->assertSame('1 / 1', $document->evaluate('string(//*[@data-gallery-count])'));
        $this->assertStringContainsString('Les tarifs par chambre sont disponibles sur demande.', $html);
    }
}
