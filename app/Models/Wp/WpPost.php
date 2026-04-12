<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;

class WpPost extends Model
{
    /**
     * WordPress DB connection (see config/database.php → wp: host/database/prefix WP_DB_*).
     * Table resolves to {prefix}posts (e.g. cFdgeZ_posts when WP_DB_PREFIX=cFdgeZ_).
     */
    protected $connection = 'wp';

    /**
     * WordPress posts table (without prefix; Laravel prepends the connection prefix).
     */
    protected $table = 'posts';

    /**
     * Primary key.
     */
    protected $primaryKey = 'ID';

    /**
     * WordPress doesn't use Laravel timestamps.
     */
    public $timestamps = false;

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count',
    ];

    /**
     * Casts.
     */
    protected $casts = [
        'ID' => 'integer',
        'post_author' => 'integer',
        'post_parent' => 'integer',
        'menu_order' => 'integer',
        'comment_count' => 'integer',
        'post_date' => 'datetime',
        'post_date_gmt' => 'datetime',
        'post_modified' => 'datetime',
        'post_modified_gmt' => 'datetime',
    ];

    /**
     * Post meta relationship.
     */
    public function metas()
    {
        return $this->hasMany(WpPostMeta::class, 'post_id', 'ID');
    }

    public function stActivity()
    {
        return $this->hasOne(StActivity::class, 'post_id', 'ID');
    }

    public function stCar()
    {
        return $this->hasOne(StCar::class, 'post_id', 'ID');
    }

    /**
     * Scope for tours (st_tours post type).
     */
    public function scopeTours($query)
    {
        return $query->where('post_type', 'st_tours');
    }

    /**
     * Scope for published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('post_status', 'publish');
    }

    /**
     * Get a single meta value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMeta(string $key, $default = null)
    {
        $meta = $this->metas()->where('meta_key', $key)->first();
        
        return $meta ? $meta->meta_value : $default;
    }

    /**
     * Set (upsert) a meta value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setMeta(string $key, $value): void
    {
        $meta = $this->metas()->where('meta_key', $key)->first();

        if ($meta) {
            $meta->update(['meta_value' => $value]);
        } else {
            $this->metas()->create([
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
    }

    /**
     * Delete a meta key.
     *
     * @param string $key
     * @return void
     */
    public function deleteMeta(string $key): void
    {
        $this->metas()->where('meta_key', $key)->delete();
    }

    /**
     * Get all metas as key-value array.
     *
     * @return array
     */
    public function getAllMetas(): array
    {
        return $this->metas()
            ->get()
            ->pluck('meta_value', 'meta_key')
            ->toArray();
    }

    /**
     * Set multiple metas at once.
     *
     * @param array $metas ['key' => 'value', ...]
     * @return void
     */
    public function setMetas(array $metas): void
    {
        foreach ($metas as $key => $value) {
            if ($value !== null) {
                $this->setMeta($key, $value);
            }
        }
    }
}
