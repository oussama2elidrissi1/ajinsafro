<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'client_code',
        'partner_id',
        'branch_id',
        'client_type',
        'status',
        'source',
        'assigned_to',
        'first_name',
        'last_name',
        'full_name',
        'gender',
        'date_of_birth',
        'nationality',
        'country_of_residence',
        'city',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'preferred_language',
        'email',
        'phone',
        'phone_alt',
        'whatsapp_number',
        'website',
        'contact_method_preference',
        'passport_number',
        'passport_issue_country',
        'passport_issue_date',
        'passport_expiry_date',
        'national_id_number',
        'visa_required',
        'visa_status',
        'traveler_category',
        'preferred_departure_city',
        'preferred_destination',
        'preferred_travel_month',
        'budget_min',
        'budget_max',
        'travel_interests',
        'special_requests',
        'medical_notes',
        'dietary_requirements',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'company_name',
        'company_registration_number',
        'tax_number',
        'company_contact_person',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_postal_code',
        'payment_terms',
        'credit_limit',
        'newsletter_opt_in',
        'sms_opt_in',
        'whatsapp_opt_in',
        'loyalty_points',
        'last_contacted_at',
        'next_follow_up_at',

        // Client portal account link (Laravel users)
        'user_id',
        'portal_username',
        'portal_temp_password',
        'portal_temp_password_created_at',

        'avatar',
        'internal_notes',
        'blacklist_reason',
        'is_duplicate',
        'merged_into_client_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_issue_date' => 'date',
        'passport_expiry_date' => 'date',
        'visa_required' => 'boolean',
        'newsletter_opt_in' => 'boolean',
        'sms_opt_in' => 'boolean',
        'whatsapp_opt_in' => 'boolean',
        'is_duplicate' => 'boolean',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'travel_interests' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Client $client): void {
            if (empty($client->uuid)) {
                $client->uuid = (string) Str::uuid();
            }
            if (empty($client->client_code)) {
                $client->client_code = self::generateClientCode();
            }
            if (empty($client->full_name) && ($client->first_name || $client->last_name)) {
                $client->full_name = trim($client->first_name . ' ' . $client->last_name);
            }
            if (auth()->check()) {
                $client->created_by = $client->created_by ?? auth()->id();
            }
        });

        static::updating(function (Client $client): void {
            if (($client->isDirty('first_name') || $client->isDirty('last_name')) &&
                ($client->first_name || $client->last_name)) {
                $client->full_name = trim($client->first_name . ' ' . $client->last_name);
            }
            if (auth()->check()) {
                $client->updated_by = auth()->id();
            }
        });
    }

    public static function generateClientCode(): string
    {
        $year = date('Y');
        $last = static::withTrashed()
            ->where('client_code', 'like', 'CL-' . $year . '-%')
            ->orderByDesc('id')
            ->first();
        $num = $last ? (int) substr($last->client_code, -4) + 1 : 1;
        return 'CL-' . $year . '-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'merged_into_client_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeVip(Builder $query): Builder
    {
        return $query->where('status', 'vip');
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', 'blocked');
    }

    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'client_external_id');
    }

    public function groupDealParticipants(): HasMany
    {
        return $this->hasMany(GroupDealParticipant::class);
    }

    public function getBudgetDisplayAttribute(): ?string
    {
        if ($this->budget_min !== null && $this->budget_max !== null) {
            return number_format($this->budget_min, 0, ',', ' ') . ' – ' . number_format($this->budget_max, 0, ',', ' ') . ' DH';
        }
        if ($this->budget_min !== null) {
            return '≥ ' . number_format($this->budget_min, 0, ',', ' ') . ' DH';
        }
        if ($this->budget_max !== null) {
            return '≤ ' . number_format($this->budget_max, 0, ',', ' ') . ' DH';
        }
        return null;
    }
}
