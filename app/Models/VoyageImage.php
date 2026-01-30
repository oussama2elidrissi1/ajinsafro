<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VoyageImage extends Model
{
    use HasFactory;

    protected $fillable = ['voyage_id', 'path', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    /**
     * Public URL for the stored image (public disk).
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
