<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationAssignmentLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'old_branch_id',
        'new_branch_id',
        'old_agent_id',
        'new_agent_id',
        'old_sales_manager_id',
        'new_sales_manager_id',
        'changed_by',
        'note',
        'created_at',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'old_branch_id' => 'integer',
        'new_branch_id' => 'integer',
        'old_agent_id' => 'integer',
        'new_agent_id' => 'integer',
        'old_sales_manager_id' => 'integer',
        'new_sales_manager_id' => 'integer',
        'changed_by' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            if (! $log->created_at) {
                $log->created_at = now();
            }
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
