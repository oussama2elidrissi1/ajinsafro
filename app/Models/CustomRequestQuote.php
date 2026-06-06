<?php

namespace App\Models;

use App\Services\CustomRequestQuotePdfService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CustomRequestQuote extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_SENT = 'sent';
    public const STATUS_MODIFICATION_REQUESTED = 'modification_requested';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';

    protected $fillable = [
        'custom_request_id', 'quote_number', 'version', 'created_by', 'status', 'currency',
        'supplier_name', 'valid_until', 'total_purchase', 'total_margin', 'total_sale',
        'requested_deposit', 'paid_amount', 'remaining_amount', 'customer_conditions',
        'internal_notes', 'pdf_path', 'prepared_at', 'sent_at',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'prepared_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_purchase' => 'decimal:2',
        'total_margin' => 'decimal:2',
        'total_sale' => 'decimal:2',
        'requested_deposit' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quote): void {
            if (! $quote->quote_number) {
                $quote->quote_number = self::generateQuoteNumber();
            }
        });
    }

    public static function generateQuoteNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'DEV-DAC-'.$year.'-';
        $last = self::query()
            ->where('quote_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('quote_number');

        $next = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $items = $this->items()->get();
        $totalPurchase = (float) $items->sum('total_purchase');
        $totalMargin = (float) $items->sum('total_margin');
        $totalSale = (float) $items->sum('total_sale');

        $this->forceFill([
            'total_purchase' => $totalPurchase,
            'total_margin' => $totalMargin,
            'total_sale' => $totalSale,
            'remaining_amount' => max(0, $totalSale - (float) ($this->paid_amount ?? 0)),
        ])->save();
    }

    public function generatePdf(): string
    {
        $path = app(CustomRequestQuotePdfService::class)->generate($this->fresh(['customRequest', 'items']));
        $this->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    public function markAsPrepared(): void
    {
        $this->forceFill(['status' => self::STATUS_PREPARED, 'prepared_at' => now()])->save();
    }

    public function markAsSent(): void
    {
        $this->forceFill(['status' => self::STATUS_SENT, 'sent_at' => now()])->save();
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PREPARED => 'Préparé',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_MODIFICATION_REQUESTED => 'Modification demandée',
            self::STATUS_ACCEPTED => 'Accepté',
            self::STATUS_REFUSED => 'Refusé',
        ];
    }

    public static function itemServiceOptions(): array
    {
        return [
            'hotel' => 'Hôtel', 'flight' => 'Vol', 'transfer' => 'Transfert', 'visa' => 'Visa',
            'insurance' => 'Assurance', 'activity' => 'Activité', 'transport' => 'Transport', 'other' => 'Autre',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline((string) $this->status);
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomRequestQuoteItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function generatedDocument(): HasOne
    {
        return $this->hasOne(CustomRequestDocument::class, 'quote_id')->where('document_type', 'quote');
    }
}
