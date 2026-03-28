<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerDocument extends Model
{
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_COMMISSION_GRID = 'commission_grid';
    public const TYPE_CONDITIONS = 'conditions';
    public const TYPE_MARKETING = 'marketing';

    protected $fillable = ['partner_id', 'type', 'name', 'file_path'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_CONTRACT => 'Contrat partenaire',
            self::TYPE_COMMISSION_GRID => 'Grille de commission',
            self::TYPE_CONDITIONS => 'Conditions de vente',
            self::TYPE_MARKETING => 'Support marketing',
        ];
    }
}
