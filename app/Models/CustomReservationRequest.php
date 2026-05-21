<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomReservationRequest extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_NEW = 'new';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'reference',
        'status',
        'priority',
        'source',
        'assigned_to',
        'created_by',
        'client_type',
        'client_name',
        'client_gender',
        'client_phone',
        'client_whatsapp',
        'whatsapp_same_as_phone',
        'client_email',
        'preferred_channels',
        'adults',
        'children',
        'infants',
        'passengers_note',
        'destination_text',
        'departure_city_text',
        'departure_date',
        'return_date',
        'flexible_dates',
        'budget_min',
        'budget_max',
        'currency',
        'services',
        'internal_notes',
        'client_notes',
        'admin_response',
        'quoted_amount',
        'converted_reservation_id',
    ];

    protected $casts = [
        'preferred_channels' => 'array',
        'children' => 'array',
        'infants' => 'array',
        'services' => 'array',
        'flexible_dates' => 'boolean',
        'whatsapp_same_as_phone' => 'boolean',
        'departure_date' => 'date',
        'return_date' => 'date',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'quoted_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (! $request->reference) {
                $request->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year = now()->format('Y');
        $prefix = 'DMD-'.$year.'-';
        $lastReference = self::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('reference');

        $next = 1;
        if (is_string($lastReference) && preg_match('/-(\d+)$/', $lastReference, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_NEW => 'Nouvelle',
            self::STATUS_IN_REVIEW => 'En traitement',
            self::STATUS_QUOTED => 'Devis envoye',
            self::STATUS_ACCEPTED => 'Acceptee',
            self::STATUS_CONVERTED => 'Convertie',
            self::STATUS_CANCELLED => 'Annulee',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_NORMAL => 'Normale',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }

    public static function sourceOptions(): array
    {
        return [
            'admin' => 'Admin',
            'phone' => 'Telephone',
            'whatsapp' => 'WhatsApp',
            'website' => 'Site web',
            'agency' => 'Agence',
        ];
    }

    public static function serviceOptions(): array
    {
        return [
            'flights' => 'Vols',
            'accommodation' => 'Hebergement',
            'transfers' => 'Transferts',
            'excursions' => 'Excursions',
            'omra' => 'Omra',
            'visa' => 'Visa',
            'insurance' => 'Assurance',
            'other' => 'Autre',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline((string) $this->status);
    }

    public function priorityLabel(): string
    {
        return $this->priority ? (self::priorityOptions()[$this->priority] ?? Str::headline($this->priority)) : '-';
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'converted_reservation_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isSiegeAdmin() || $user->isBranchAdmin() || $user->isManager() || $user->isChefCommercial()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('created_by', $user->id)
                ->orWhere('assigned_to', $user->id);
        });
    }
}
