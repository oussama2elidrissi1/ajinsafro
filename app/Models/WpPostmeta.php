<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WpPostmeta extends Model
{
    protected $table = 'cFdgeZ_postmeta';

    protected $primaryKey = 'meta_id';

    public $timestamps = false;

    protected $fillable = ['post_id', 'meta_key', 'meta_value'];

    protected $casts = [
        'post_id' => 'integer',
    ];

    public function post()
    {
        return $this->belongsTo(WpPost::class, 'post_id', 'ID');
    }

    /**
     * Get a single meta value for a post.
     */
    public static function getMeta(int $postId, string $metaKey): ?string
    {
        $row = static::where('post_id', $postId)->where('meta_key', $metaKey)->first();

        return $row ? $row->meta_value : null;
    }

    /**
     * Set (update or insert) a single meta value for a post.
     */
    public static function setMeta(int $postId, string $metaKey, ?string $metaValue): void
    {
        $row = static::where('post_id', $postId)->where('meta_key', $metaKey)->first();
        if ($row) {
            if ($metaValue === null || $metaValue === '') {
                $row->delete();
            } else {
                $row->meta_value = $metaValue;
                $row->save();
            }
        } else {
            if ($metaValue !== null && $metaValue !== '') {
                static::create([
                    'post_id' => $postId,
                    'meta_key' => $metaKey,
                    'meta_value' => $metaValue,
                ]);
            }
        }
    }
}
