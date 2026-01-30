<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelProgramDay extends Model
{
    use HasFactory;

    public const DAY_TYPES = [
        'arrivee'  => 'Arrivée',
        'visite'   => 'Visite',
        'transfert' => 'Transfert',
        'libre'    => 'Libre',
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

    public function getDayTypeLabelAttribute(): string
    {
        return self::DAY_TYPES[$this->day_type] ?? $this->day_type;
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
