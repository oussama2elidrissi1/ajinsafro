<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerWalletTransaction extends Model
{
    public const TYPE_RECHARGE = 'recharge';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'partner_id',
        'type',
        'amount',
        'payment_method',
        'proof_path',
        'status',
        'note',
        'admin_note',
        'requested_by',
        'validated_by',
        'validated_at',
        'balance_before',
        'balance_after',
        'reservation_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'validated_at' => 'datetime',
    ];

    /** Libelle du type d'operation, tel qu'il est montre au partenaire. */
    public function getTypeLabelAttribute(): string
    {
        return match ((string) $this->type) {
            self::TYPE_RECHARGE => 'Recharge',
            self::TYPE_DEBIT => 'Débit réservation',
            self::TYPE_REFUND => 'Remboursement',
            self::TYPE_ADJUSTMENT => 'Ajustement',
            default => 'Opération',
        };
    }

    /** Libelle de l'etat de validation. */
    public function getStatusLabelAttribute(): string
    {
        return match ((string) $this->status) {
            self::STATUS_APPROVED => 'Validée',
            self::STATUS_REJECTED => 'Refusée',
            self::STATUS_PENDING => 'En attente',
            default => 'En attente',
        };
    }

    /** Tonalite d'affichage de l'etat : ok, warn ou off. */
    public function getStatusToneAttribute(): string
    {
        return match ((string) $this->status) {
            self::STATUS_APPROVED => 'ok',
            self::STATUS_REJECTED => 'off',
            default => 'warn',
        };
    }

    /** Libelle du mode de paiement, valeurs acceptees par le formulaire. */
    public function getPaymentMethodLabelAttribute(): ?string
    {
        $method = trim((string) $this->payment_method);

        if ($method === '') {
            return null;
        }

        return self::paymentMethods()[$method] ?? ucfirst($method);
    }

    /** Vrai quand l'operation augmente le solde. */
    public function getIsCreditAttribute(): bool
    {
        return in_array((string) $this->type, [self::TYPE_RECHARGE, self::TYPE_REFUND], true);
    }

    /**
     * Modes de paiement proposes, identiques a ceux valides par le controleur.
     *
     * @return array<string, string>
     */
    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Espèces',
            'virement' => 'Virement bancaire',
            'cheque' => 'Chèque',
            'carte' => 'Carte bancaire',
            'autre' => 'Autre',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
