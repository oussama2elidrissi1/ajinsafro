<?php

namespace App\Models;

use App\Services\BusinessReferentialService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelProgramDay extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     * Explicitly set to 'mysql' to ensure pivot tables use the correct connection.
     *
     * @var string|null
     */
    protected $connection = 'mysql';

    /** Types affichés (pilotables via Paramètres > Référentiels). Valeurs stables : aboard, visite, libre. */
    public const DAY_TYPES = [
        'aboard' => 'À bord',
        'visite' => 'Visite',
        'libre' => 'Libre',
    ];

    public const DAY_LABELS = [
        'inclus'   => 'Inclus',
        'optionnel' => 'Optionnel',
        'libre'    => 'Libre',
    ];

    protected $fillable = [
        'voyage_id',
        'day_number',
        'title',
        'city',
        'day_label',
        'meals_json',
        'content_html',
        'description',
        'nights',
        'day_type',
        'meal_breakfast',
        'meal_lunch',
        'meal_dinner',
        'hotel_id',
    ];

    protected $casts = [
        'nights' => 'integer',
        'meal_breakfast' => 'boolean',
        'meal_lunch' => 'boolean',
        'meal_dinner' => 'boolean',
        'meals_json' => 'array',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function hotel()
    {
        return $this->belongsTo(TourHotel::class, 'hotel_id');
    }

    public function transfers()
    {
        return $this->belongsToMany(TourTransfer::class, 'program_day_transfers', 'program_day_id', 'transfer_id')
            ->using(ProgramDayTransfer::class);
    }

    public function items()
    {
        return $this->hasMany(TravelDayItem::class, 'day_number', 'day_number')
            ->where('voyage_id', $this->voyage_id)
            ->orderBy('sort_order');
    }

    public function getDayTypeLabelAttribute(): string
    {
        foreach (BusinessReferentialService::programDayTypes() as $row) {
            if (($row['value'] ?? '') === $this->day_type) {
                return (string) ($row['label'] ?? $this->day_type);
            }
        }

        return self::DAY_TYPES[$this->day_type] ?? $this->day_type;
    }

    /** Anciennes valeurs (arrivee, transfert) → types actuels. */
    public static function normalizeDayType(?string $value): string
    {
        $v = (string) ($value ?? 'visite');
        $map = [
            'arrivee' => 'aboard',
            'transfert' => 'visite',
        ];
        $v = $map[$v] ?? $v;

        $allowed = array_column(BusinessReferentialService::programDayTypes(), 'value');
        if ($allowed === []) {
            $allowed = array_keys(self::DAY_TYPES);
        }

        return in_array($v, $allowed, true) ? $v : 'visite';
    }

    public function getDayLabelBadgeAttribute(): string
    {
        return self::DAY_LABELS[$this->day_label] ?? $this->day_label ?? '';
    }

    /** @return array{breakfast?: bool, lunch?: bool, dinner?: bool} */
    public function getMealsArrayAttribute(): array
    {
        if (is_array($this->meals_json)) {
            return array_merge(
                ['breakfast' => false, 'lunch' => false, 'dinner' => false],
                $this->meals_json
            );
        }
        return [
            'breakfast' => $this->meal_breakfast ?? false,
            'lunch' => $this->meal_lunch ?? false,
            'dinner' => $this->meal_dinner ?? false,
        ];
    }

    public function hasMealBreakfast(): bool
    {
        $m = $this->meals_array;
        return ($m['breakfast'] ?? false) || $this->meal_breakfast;
    }

    public function hasMealLunch(): bool
    {
        $m = $this->meals_array;
        return ($m['lunch'] ?? false) || $this->meal_lunch;
    }

    public function hasMealDinner(): bool
    {
        $m = $this->meals_array;
        return ($m['dinner'] ?? false) || $this->meal_dinner;
    }

    public function getContentForDisplayAttribute(): string
    {
        return $this->content_html ?: (string) $this->description;
    }
}
