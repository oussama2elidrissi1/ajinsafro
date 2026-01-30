<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WpPost extends Model
{
    protected $table = 'cFdgeZ_posts';

    protected $primaryKey = 'ID';

    public $incrementing = true;

    public $timestamps = false;

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

    protected $casts = [
        'post_parent' => 'integer',
        'menu_order' => 'integer',
        'comment_count' => 'integer',
    ];

    public function stHotel()
    {
        return $this->hasOne(StHotel::class, 'post_id', 'ID');
    }

    /**
     * Scope: only st_hotel post type.
     */
    public function scopeTypeHotel($query)
    {
        return $query->where('post_type', 'st_hotel');
    }

    /**
     * Scope: publish and draft (exclude trash).
     */
    public function scopePublishedOrDraft($query)
    {
        return $query->whereIn('post_status', ['publish', 'draft']);
    }

    /**
     * Generate a unique post_name (slug) for the given title.
     * Optionally exclude a post ID (for updates).
     */
    public static function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        if (empty($base)) {
            $base = 'hotel';
        }
        $slug = $base;
        $n = 1;
        while (true) {
            $query = static::where('post_name', $slug)->where('post_type', 'st_hotel');
            if ($excludeId !== null) {
                $query->where('ID', '!=', $excludeId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
