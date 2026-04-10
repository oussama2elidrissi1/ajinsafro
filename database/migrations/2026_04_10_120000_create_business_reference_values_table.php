<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_reference_values', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 80)->index();
            $table->string('value', 255);
            $table->string('label', 500);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['group_key', 'value']);
        });

        $this->seedFromDefaults();
    }

    private function seedFromDefaults(): void
    {
        $defaults = [
            'program_day_types' => [
                ['value' => 'aboard', 'label' => 'À bord'],
                ['value' => 'visite', 'label' => 'Visite'],
                ['value' => 'libre', 'label' => 'Libre'],
                ['value' => 'arrivee', 'label' => 'Arrivée'],
                ['value' => 'transfert', 'label' => 'Transfert'],
            ],
            'theme_types' => [],
            'logistics_transport_types' => [
                ['value' => 'flight', 'label' => 'Vol'],
                ['value' => 'transfer', 'label' => 'Transfert'],
                ['value' => 'train', 'label' => 'Train'],
                ['value' => 'boat', 'label' => 'Bateau'],
                ['value' => 'transport', 'label' => 'Transport'],
            ],
            'discount_types' => [
                ['value' => 'fixed', 'label' => 'Montant fixe'],
                ['value' => 'percent', 'label' => 'Pourcentage'],
            ],
            'discount_scopes' => [
                ['value' => 'global', 'label' => 'Globale'],
                ['value' => 'adult', 'label' => 'Adulte'],
                ['value' => 'child', 'label' => 'Enfant'],
                ['value' => 'infant', 'label' => 'Bébé'],
                ['value' => 'room', 'label' => 'Chambre'],
                ['value' => 'period', 'label' => 'Période'],
            ],
            'discount_conditions' => [
                ['value' => 'none', 'label' => 'Aucune'],
                ['value' => 'date_range', 'label' => 'Période de dates'],
                ['value' => 'min_pax', 'label' => 'Nombre de personnes minimum'],
                ['value' => 'promo_code', 'label' => 'Code promo'],
                ['value' => 'early_booking', 'label' => 'Early booking'],
                ['value' => 'last_minute', 'label' => 'Last minute'],
            ],
            'payment_methods' => [
                ['value' => 'is_meta_payment_gateway_st_paypal', 'label' => 'PayPal', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_paypal']],
                ['value' => 'is_meta_payment_gateway_st_onepay', 'label' => 'OnePay', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_onepay']],
                ['value' => 'is_meta_payment_gateway_st_onepay_atm', 'label' => 'OnePay ATM', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_onepay_atm']],
                ['value' => 'is_meta_payment_gateway_st_payu', 'label' => 'PayU', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_payu']],
                ['value' => 'is_meta_payment_gateway_st_payulatam', 'label' => 'PayU Latam', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_payulatam']],
                ['value' => 'is_meta_payment_gateway_st_payumoney', 'label' => 'PayUmoney', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_payumoney']],
                ['value' => 'is_meta_payment_gateway_st_razor', 'label' => 'Razorpay', 'meta' => ['meta_key' => 'is_meta_payment_gateway_st_razor']],
            ],
            'activity_types' => [],
            'room_types' => [],
            'voyage_activity_pricing_types' => [
                ['value' => 'per_person', 'label' => 'Par personne'],
                ['value' => 'fixed', 'label' => 'Fixe'],
            ],
            'tour_price_by' => [
                ['value' => 'person', 'label' => 'Par personne'],
                ['value' => 'group', 'label' => 'Par groupe'],
                ['value' => 'fixed', 'label' => 'Prix fixe'],
                ['value' => 'room', 'label' => 'Par chambre'],
            ],
        ];

        $now = now();
        $rows = [];
        foreach ($defaults as $groupKey => $items) {
            $sort = 0;
            foreach ($items as $item) {
                $rows[] = [
                    'group_key' => $groupKey,
                    'value' => $item['value'],
                    'label' => $item['label'],
                    'is_active' => true,
                    'sort_order' => $sort++,
                    'meta' => isset($item['meta']) ? json_encode($item['meta']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table('business_reference_values')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_reference_values');
    }
};
