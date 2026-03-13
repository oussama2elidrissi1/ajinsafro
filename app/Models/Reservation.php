<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    /**
     * Connexion Laravel principale (et non la connexion WordPress `wp`).
     */
    protected $connection = 'mysql';

    protected $table = 'reservations';

    public const STATUS_EN_COURS  = 'EN_COURS';
    public const STATUS_VALIDEE   = 'VALIDEE';
    public const STATUS_ANNULEE   = 'ANNULEE';

    public const PAYMENT_CASHPLUS = 'CASHPLUS';
    public const PAYMENT_VIREMENT = 'VIREMENT';
    public const PAYMENT_ESPECE   = 'ESPECE';

    protected $fillable = [
        'tour_id',
        'client_mode',
        'client_external_id',
        'client_first_name',
        'client_last_name',
        'client_email',
        'client_phone',
        'client_document_type',
        'client_document_number',
        'payment_type',
        'payment_receipt_path',
        'status',
        'passengers_count',
        'notes',
    ];

    protected $casts = [
        'tour_id'          => 'integer',
        'client_external_id' => 'integer',
        'passengers_count' => 'integer',
    ];

    public function passengers(): HasMany
    {
        return $this->hasMany(ReservationPassenger::class);
    }
}

