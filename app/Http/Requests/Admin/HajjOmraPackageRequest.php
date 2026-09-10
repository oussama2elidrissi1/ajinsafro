<?php

namespace App\Http\Requests\Admin;

use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraPackageHotel;
use App\Models\HajjOmraRoomPrice;
use App\Models\HajjOmraServiceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation serveur de l'editeur d'offre Hajj & Omra.
 *
 * Regle de langue : au moins un titre FR ou AR. Les anciennes
 * offres, saisies avant l'ajout de l'arabe, restent donc enregistrables sans blocage.
 * L'editeur signale visuellement les traductions manquantes.
 * Pour rendre l'arabe obligatoire plus tard, il suffit de passer
 * self::REQUIRE_ARABIC a true : les regles s'ajustent automatiquement.
 */
class HajjOmraPackageRequest extends FormRequest
{
    /** Bascule unique pour rendre la traduction arabe obligatoire. */
    public const REQUIRE_ARABIC = false;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('hajj-omra.view');
    }

    /**
     * Le formulaire expose `title_fr` (plus lisible cote UI) alors que la colonne
     * historique s'appelle `title`. On normalise avant validation pour ne pas avoir
     * a renommer une colonne deja consommee par l'API publique.
     */
    protected function prepareForValidation(): void
    {
        $aliases = [
            'title_fr' => 'title',
            'short_description_fr' => 'short_description',
            'description_fr' => 'description',
            'booking_conditions_fr' => 'booking_conditions',
            'required_documents_fr' => 'required_documents',
            'meta_title_fr' => 'meta_title',
            'meta_description_fr' => 'meta_description',
        ];

        $merge = [];

        foreach ($aliases as $formField => $column) {
            if ($this->has($formField)) {
                $merge[$column] = $this->input($formField);
            }
        }

        if ($this->boolean('formulas_present') && ! $this->has('formulas')) {
            $merge['formulas'] = [];
        }
        if (! $this->filled('title') && ! $this->filled('title_fr') && $this->filled('title_ar')) {
            $merge['title'] = '';
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $packageId = $this->route('hajjOmraPackage')?->id;
        $arabic = self::REQUIRE_ARABIC ? ['required', 'string'] : ['nullable', 'string'];

        return [
            // --- Etape 1 : offre ---
            'title' => ['required_without:title_ar', 'nullable', 'string', 'max:255'],
            'title_ar' => array_merge($arabic, ['max:255']),
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('hajj_omra_packages', 'slug')->ignore($packageId)],
            'type' => ['required', Rule::in(HajjOmraPackage::TYPES)],
            'status' => ['required', Rule::in(HajjOmraPackage::STATUSES)],
            'departure_city' => ['nullable', 'string', 'max:150'],
            'destination' => ['nullable', 'string', 'max:150'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'duration_nights' => ['nullable', 'integer', 'min:0', 'max:365'],
            'start_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'short_description' => ['nullable', 'string', 'max:1200'],
            'short_description_ar' => array_merge($arabic, ['max:1200']),
            'description' => ['nullable', 'string'],
            'description_ar' => $arabic,

            // --- Prix principal ---
            'adult_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'old_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'child_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'baby_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'currency' => ['required', 'string', 'max:10'],
            'available_places' => ['nullable', 'integer', 'min:0'],
            'reserved_places' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],

            // --- Etape 2 : tarifs & chambres ---
            'room_prices' => ['nullable', 'array'],
            'room_prices.*' => ['array'],
            'room_prices.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('hajj_omra_room_prices', 'id')->where('package_id', $packageId ?? 0)],
            'room_prices.*.client_key' => ['nullable', 'string', 'max:80', 'distinct', 'regex:/^[A-Za-z0-9_-]+$/'],
            'room_prices.*.room_type' => ['nullable', Rule::in(HajjOmraRoomPrice::ROOM_TYPES)],
            'room_prices.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'room_prices.*.old_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'room_prices.*.capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'room_prices.*.stock' => ['nullable', 'integer', 'min:0'],
            'room_prices.*.is_active' => ['nullable', 'boolean'],

            // Formula references may point to persisted IDs or new rows in this same save.
            'formulas_present' => ['nullable', 'boolean'],
            'formulas' => ['nullable', 'array', 'max:40'],
            'formulas.*' => ['array'],
            'formulas.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('hajj_omra_formulas', 'id')->where('package_id', $packageId ?? 0)],
            'formulas.*.name_fr' => ['nullable', 'string', 'max:255'],
            'formulas.*.name_ar' => ['nullable', 'string', 'max:255'],
            'formulas.*.description_fr' => ['nullable', 'string', 'max:4000'],
            'formulas.*.description_ar' => ['nullable', 'string', 'max:4000'],
            'formulas.*.is_active' => ['nullable', 'boolean'],
            'formulas.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'formulas.*.departure_id' => ['nullable', 'regex:/^(?:[1-9][0-9]*|new:[A-Za-z0-9_-]+)$/'],
            'formulas.*.tariff_ids' => ['nullable', 'array', 'max:20'],
            'formulas.*.tariff_ids.*' => ['required', 'regex:/^(?:[1-9][0-9]*|new:[A-Za-z0-9_-]+)$/'],
            'formulas.*.hotels' => ['nullable', 'array', 'max:20'],
            'formulas.*.hotels.*' => ['array'],
            'formulas.*.hotels.*.hotel_id' => ['nullable', 'regex:/^(?:[1-9][0-9]*|new:[A-Za-z0-9_-]+)$/'],
            'formulas.*.hotels.*.program_day_id' => ['nullable', 'regex:/^(?:[1-9][0-9]*|new:[A-Za-z0-9_-]+)$/'],
            'formulas.*.hotels.*.nights_override' => ['nullable', 'integer', 'min:0', 'max:365'],

            // --- Etape 3 : departs ---
            'departures' => ['nullable', 'array'],
            'departures.*' => ['array'],
            'departures.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('hajj_omra_departures', 'id')->where('package_id', $packageId ?? 0)],
            'departures.*.client_key' => ['nullable', 'string', 'max:80', 'distinct', 'regex:/^[A-Za-z0-9_-]+$/'],
            'departures.*.departure_date' => ['nullable', 'date', 'distinct'],
            'departures.*.return_date' => ['nullable', 'date', 'after_or_equal:departures.*.departure_date'],
            'departures.*.departure_city' => ['nullable', 'string', 'max:150'],
            'departures.*.status' => ['nullable', Rule::in(HajjOmraDeparture::STATUSES)],
            'departures.*.available_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.reserved_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.price_from' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'departures.*.internal_notes' => ['nullable', 'string', 'max:2000'],

            // --- Etape 4 : hebergement ---
            'hotels' => ['nullable', 'array'],
            'hotels.*' => ['array'],
            'hotels.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('hajj_omra_package_hotels', 'id')->where('package_id', $packageId ?? 0)],
            'hotels.*.client_key' => ['nullable', 'string', 'max:80', 'distinct', 'regex:/^[A-Za-z0-9_-]+$/'],
            'hotels.*.city' => ['nullable', Rule::in(array_keys(HajjOmraPackageHotel::CITY_LABELS))],
            'hotels.*.name_ar' => ['nullable', 'string', 'max:255'],
            'hotels.*.location_ar' => ['nullable', 'string', 'max:255'],
            'hotels.*.name' => ['nullable', 'string', 'max:255'],
            'hotels.*.stars' => ['nullable', 'integer', 'min:1', 'max:5'],
            'hotels.*.haram_distance' => ['nullable', 'string', 'max:100'],
            'hotels.*.location' => ['nullable', 'string', 'max:255'],
            'hotels.*.nights' => ['nullable', 'integer', 'min:0', 'max:365'],
            'hotels.*.meal_plan' => ['nullable', Rule::in(array_keys(HajjOmraPackage::mealPlanOptions()))],
            'hotels.*.description' => ['nullable', 'string', 'max:4000'],
            'hotels.*.description_ar' => ['nullable', 'string', 'max:4000'],
            'hotels.*.image_path' => ['nullable', 'string', 'max:255'],

            // --- Etape 5 : programme ---
            'program_days' => ['nullable', 'array'],
            'program_days.*' => ['array'],
            'program_days.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('hajj_omra_program_days', 'id')->where('package_id', $packageId ?? 0)],
            'program_days.*.client_key' => ['nullable', 'string', 'max:80', 'distinct', 'regex:/^[A-Za-z0-9_-]+$/'],
            'program_days.*.day_number' => ['nullable', 'integer', 'min:1', 'max:365'],
            'program_days.*.title' => ['nullable', 'string', 'max:255'],
            'program_days.*.title_ar' => ['nullable', 'string', 'max:255'],
            'program_days.*.city' => ['nullable', 'string', 'max:120'],
            'program_days.*.description' => ['nullable', 'string'],
            'program_days.*.description_ar' => ['nullable', 'string'],
            'program_days.*.image_path' => ['nullable', 'string', 'max:255'],

            // --- Etape 6 : prestations ---
            'service_items' => ['nullable', 'array'],
            'service_items.*' => ['array'],
            'service_items.*.id' => ['nullable', 'integer'],
            'service_items.*.kind' => ['nullable', Rule::in(HajjOmraServiceItem::KINDS)],
            'service_items.*.label' => ['nullable', 'string', 'max:255'],
            'service_items.*.label_ar' => ['nullable', 'string', 'max:255'],
            'booking_conditions' => ['nullable', 'string'],
            'booking_conditions_ar' => ['nullable', 'string'],
            'required_documents' => ['nullable', 'string'],
            'required_documents_ar' => ['nullable', 'string'],
            'meal_plan' => ['nullable', Rule::in(array_keys(HajjOmraPackage::mealPlanOptions()))],
            'transport_included' => ['nullable', 'boolean'],
            'visa_included' => ['nullable', 'boolean'],
            'guidance_included' => ['nullable', 'boolean'],

            // --- Etape 7 : medias ---
            // Les images passent par l'uploader existant (admin.local-media.upload)
            // et arrivent ici sous forme de chemins deja stockes.
            'main_image' => ['nullable', 'string', 'max:255'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['array'],
            'gallery.*.id' => ['nullable', 'integer'],
            'gallery.*.image_path' => ['nullable', 'string', 'max:255'],
            'gallery.*.alt_text' => ['nullable', 'string', 'max:255'],

            // --- Etape 8 : publication & SEO ---
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_description_ar' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required_without' => 'Renseignez le titre français ou arabe de l’offre.',
            'formulas.*.id.exists' => 'Cette formule ne fait pas partie de l’offre.',
            'formulas.*.name_fr.max' => 'Le nom de la formule ne peut pas dépasser 255 caractères.',
            'departures.*.departure_date.distinct' => 'Chaque date de départ doit être unique dans cette offre.',
            'duration_days.required' => 'Le nombre de jours est obligatoire : il pilote la generation du programme.',
            'duration_days.min' => 'L\'offre doit durer au moins 1 jour.',
            'return_date.after_or_equal' => 'La date de retour doit etre posterieure ou egale a la date de depart.',
            'departures.*.return_date.after_or_equal' => 'La date de retour d\'un depart doit suivre sa date de depart.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titre francais',
            'title_ar' => 'titre arabe',
            'duration_days' => 'nombre de jours',
            'duration_nights' => 'nombre de nuits',
            'available_places' => 'places disponibles',
            'reserved_places' => 'places reservees',
        ];
    }

    /**
     * Controles metier qui depassent une regle unitaire.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) { return; }
            $reserved = (int) $this->input('reserved_places', 0);
            $available = (int) $this->input('available_places', 0);

            if ($available > 0 && $reserved > $available) {
                $validator->errors()->add(
                    'reserved_places',
                    'Les places reservees ne peuvent pas depasser les places disponibles.'
                );
            }

            foreach ((array) $this->input('departures', []) as $index => $departure) {
                if (! is_array($departure)) { continue; }
                $depAvailable = (int) ($departure['available_places'] ?? 0);
                $depReserved = (int) ($departure['reserved_places'] ?? 0);

                if ($depAvailable > 0 && $depReserved > $depAvailable) {
                    $validator->errors()->add(
                        "departures.{$index}.reserved_places",
                        'Les places reservees depassent les places disponibles de ce depart.'
                    );
                }
            }

            // Un tarif chambre sans prix n'a pas de sens commercial.
            foreach ((array) $this->input('room_prices', []) as $index => $row) {
                if (! is_array($row)) { continue; }
                $type = trim((string) ($row['room_type'] ?? ''));
                $price = $row['price'] ?? null;

                if ($type !== '' && ($price === null || $price === '')) {
                    $validator->errors()->add("room_prices.{$index}.price", 'Indiquez un prix pour ce type de chambre.');
                }
            }
        });
    }
}
