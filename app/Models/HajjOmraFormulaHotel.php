<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HajjOmraFormulaHotel extends Model
{
    public $timestamps = false;

    protected $fillable = ['formula_id', 'hotel_id', 'program_day_id', 'nights_override', 'sort_order'];

    protected $casts = ['nights_override' => 'integer', 'sort_order' => 'integer'];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(HajjOmraFormula::class, 'formula_id');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackageHotel::class, 'hotel_id');
    }

    public function programDay(): BelongsTo
    {
        return $this->belongsTo(HajjOmraProgramDay::class, 'program_day_id');
    }
}
