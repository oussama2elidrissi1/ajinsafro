<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommissionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'commission_entry_id',
        'agent_id',
        'reservation_id',
        'old_status',
        'new_status',
        'old_amount',
        'new_amount',
        'action',
        'description',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function commissionEntry(): BelongsTo
    {
        return $this->belongsTo(AgentCommissionEntry::class, 'commission_entry_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
