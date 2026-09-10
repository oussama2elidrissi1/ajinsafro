<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HajjOmraFormula extends Model
{
    protected $fillable = ['package_id', 'departure_id', 'name_fr', 'name_ar', 'description_fr', 'description_ar', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackage::class, 'package_id');
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(HajjOmraDeparture::class, 'departure_id');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(HajjOmraFormulaHotel::class, 'formula_id')->orderBy('sort_order')->orderBy('id');
    }

    public function tariffs(): BelongsToMany
    {
        return $this->belongsToMany(HajjOmraRoomPrice::class, 'hajj_omra_formula_prices', 'formula_id', 'tariff_id')
            ->withPivot(['id', 'sort_order'])->orderByPivot('sort_order')->orderBy('hajj_omra_room_prices.id');
    }

    public function localized(string $field, string $locale = 'fr'): ?string
    {
        $primary = $locale === 'ar' ? 'ar' : 'fr';
        return $this->{$field.'_'.$primary} ?: $this->{$field.'_'.($primary === 'ar' ? 'fr' : 'ar')};
    }
}
