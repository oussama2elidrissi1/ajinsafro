<?php

namespace App\Models;

use App\Support\Locale\HasBilingualFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne « ce qui est inclus » ou « ce qui n'est pas inclus », bilingue et ordonnee.
 *
 * Remplace les listes JSON `included_items` / `excluded_items` du package, qui restent
 * alimentees automatiquement pour ne pas casser l'API publique.
 */
class HajjOmraServiceItem extends Model
{
    use HasBilingualFields;

    public const KIND_INCLUDED = 'included';

    public const KIND_EXCLUDED = 'excluded';

    public const KINDS = [self::KIND_INCLUDED, self::KIND_EXCLUDED];

    protected $table = 'hajj_omra_service_items';

    /** @var list<string> */
    protected array $bilingual = ['label'];

    protected $fillable = [
        'package_id',
        'kind',
        'label',
        'label_ar',
        'sort_order',
    ];

    protected $casts = [
        'package_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackage::class, 'package_id');
    }

    public function scopeIncluded(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_INCLUDED);
    }

    public function scopeExcluded(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_EXCLUDED);
    }
}
