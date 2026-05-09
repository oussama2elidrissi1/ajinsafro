<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HajjOmraRoomPrice extends Model
{
    public const ROOM_QUADRUPLE = 'quadruple';
    public const ROOM_TRIPLE = 'triple';
    public const ROOM_DOUBLE = 'double';
    public const ROOM_SINGLE = 'single';

    public const ROOM_TYPES = [
        self::ROOM_QUADRUPLE,
        self::ROOM_TRIPLE,
        self::ROOM_DOUBLE,
        self::ROOM_SINGLE,
    ];

    protected $fillable = [
        'package_id',
        'room_type',
        'price',
        'stock',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'room_type_label',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackage::class, 'package_id');
    }

    public static function roomTypeOptions(): array
    {
        return [
            self::ROOM_QUADRUPLE => 'Chambre quadruple',
            self::ROOM_TRIPLE => 'Chambre triple',
            self::ROOM_DOUBLE => 'Chambre double',
            self::ROOM_SINGLE => 'Chambre single',
        ];
    }

    public function getRoomTypeLabelAttribute(): string
    {
        return static::roomTypeOptions()[$this->room_type] ?? ucfirst((string) $this->room_type);
    }
}
