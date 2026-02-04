<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;

class WpPostMeta extends Model
{
    /**
     * WordPress connection.
     */
    protected $connection = 'wp';

    /**
     * WordPress postmeta table (without prefix, Laravel adds it automatically).
     */
    protected $table = 'postmeta';

    /**
     * Primary key.
     */
    protected $primaryKey = 'meta_id';

    /**
     * WordPress doesn't use Laravel timestamps.
     */
    public $timestamps = false;

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];

    /**
     * Casts.
     */
    protected $casts = [
        'meta_id' => 'integer',
        'post_id' => 'integer',
    ];

    /**
     * Post relationship.
     */
    public function post()
    {
        return $this->belongsTo(WpPost::class, 'post_id', 'ID');
    }
}
