<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProgramDayTransfer extends Pivot
{
    /**
     * The connection name for the model.
     * The pivot table is in the 'default' connection, not 'wp'.
     *
     * @var string|null
     */
    protected $connection = 'mysql';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'program_day_transfers';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_day_id',
        'transfer_id',
    ];
}
